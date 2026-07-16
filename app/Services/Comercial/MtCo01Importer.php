<?php

namespace App\Services\Comercial;

use App\Models\CommercialClient;
use App\Models\CommercialClientType;
use App\Models\CommercialSector;
use App\Models\CommercialService;
use App\Models\CommercialServiceType;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class MtCo01Importer
{
    /**
     * @var array<string, string>
     */
    private const SHEETS = [
        'SEG. FISICA' => CommercialService::PORTFOLIO_SEG_FISICA,
        'MONITOREO' => CommercialService::PORTFOLIO_MONITOREO,
        'OCASIONALES' => CommercialService::PORTFOLIO_OCASIONALES,
        'INACTIVOS' => CommercialService::PORTFOLIO_INACTIVOS,
    ];

    /**
     * @var array<string, string>
     */
    private const HEADER_ALIASES = [
        'no contrato' => 'contract_number',
        'nocontrato' => 'contract_number',
        'comercial' => 'advisor_name',
        'nit' => 'nit',
        'nombre cliente' => 'client_name',
        'telefono' => 'phone',
        'direccion' => 'address',
        'ciudad' => 'city',
        'r legal' => 'legal_rep_name',
        'r. legal' => 'legal_rep_name',
        'cc rl' => 'legal_rep_doc',
        'contacto' => 'contact_name',
        'cargo' => 'contact_role',
        'telefono2' => 'contact_phone',
        'correo' => 'contact_email',
        'sector' => 'sector',
        'tipo cliente' => 'client_type',
        'tipo de servicio' => 'service_type',
        'descripcion de los servicios' => 'service_description',
        'descripcion del servicio' => 'service_description',
        'p economica' => 'doc_economic_proposal',
        'p. economica' => 'doc_economic_proposal',
        'fo-co-02 formato de vinculacion' => 'doc_fo_co_02',
        'fo-co-02' => 'doc_fo_co_02',
        'consultas' => 'doc_laft_or_queries',
        'laft' => 'doc_laft_or_queries',
        'rut' => 'doc_rut',
        'ee.ff' => 'doc_financials',
        'eeff' => 'doc_financials',
        'cc rl2' => 'doc_legal_rep_id',
        'camara comercio/personeria juridica' => 'doc_chamber',
        'camara comercio' => 'doc_chamber',
        'preinst' => 'doc_preinstall',
        'contrato' => 'doc_contract',
        'anexo 2' => 'doc_annex_2',
        'fecha de inicio contrato' => 'contract_start',
        'fecha de terminacion contrato' => 'contract_end',
        'duracion (meses)' => 'duration_months',
        'duracion meses' => 'duration_months',
    ];

    /**
     * @return array{clients:int, services:int, skipped:int, sheets:array<string,int>, errors:list<string>}
     */
    public function import(string $path, bool $fresh = false): array
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("No se encontro el archivo: {$path}");
        }

        ini_set('memory_limit', '1024M');

        $stats = [
            'clients' => 0,
            'services' => 0,
            'skipped' => 0,
            'sheets' => [],
            'errors' => [],
        ];

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($path);

        DB::transaction(function () use ($spreadsheet, $fresh, &$stats): void {
            if ($fresh) {
                CommercialService::query()->delete();
                CommercialClient::query()->delete();
            }

            foreach (self::SHEETS as $sheetName => $portfolio) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (! $sheet) {
                    $stats['errors'][] = "Hoja no encontrada: {$sheetName}";
                    $stats['sheets'][$sheetName] = 0;

                    continue;
                }

                $imported = $this->importSheet($sheet, $portfolio, $stats);
                $stats['sheets'][$sheetName] = $imported;
            }
        });

        return $stats;
    }

    /**
     * @param  array{clients:int, services:int, skipped:int, sheets:array<string,int>, errors:list<string>}  $stats
     */
    private function importSheet(Worksheet $sheet, string $portfolio, array &$stats): int
    {
        $headerRow = $this->detectHeaderRow($sheet);
        if ($headerRow === null) {
            $stats['errors'][] = 'No se detecto encabezado con NIT en '.$sheet->getTitle();

            return 0;
        }

        $map = $this->mapHeaders($sheet, $headerRow);
        if (! isset($map['nit'], $map['client_name'])) {
            $stats['errors'][] = 'Encabezados incompletos (NIT/NOMBRE) en '.$sheet->getTitle();

            return 0;
        }

        $highestRow = $sheet->getHighestDataRow();
        $count = 0;

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            try {
                $raw = $this->rowValues($sheet, $row, $map);
                if ($this->isEmptyRow($raw)) {
                    $stats['skipped']++;

                    continue;
                }

                $nit = CommercialClient::normalizeNit((string) ($raw['nit'] ?? ''));
                $name = trim((string) ($raw['client_name'] ?? ''));

                if ($nit === '' || $name === '' || ! preg_match('/\d/', $nit)) {
                    $stats['skipped']++;

                    continue;
                }

                $client = CommercialClient::query()->firstOrNew(['nit' => $nit]);
                $wasNew = ! $client->exists;
                $client->fill([
                    'name' => $name,
                    'phone' => $this->nullableString($raw['phone'] ?? null),
                    'address' => $this->nullableString($raw['address'] ?? null),
                    'city' => $this->nullableString($raw['city'] ?? null),
                    'legal_rep_name' => $this->nullableString($raw['legal_rep_name'] ?? null),
                    'legal_rep_doc' => $this->nullableString($raw['legal_rep_doc'] ?? null),
                ]);
                $client->save();
                if ($wasNew) {
                    $stats['clients']++;
                }

                $contractNumber = $this->nullableString($raw['contract_number'] ?? null);
                $serviceQuery = CommercialService::query()
                    ->where('commercial_client_id', $client->id)
                    ->where('portfolio', $portfolio);

                if ($contractNumber) {
                    $serviceQuery->where('contract_number', $contractNumber);
                } else {
                    $serviceQuery->whereNull('contract_number')
                        ->where('service_description', $this->nullableString($raw['service_description'] ?? null));
                }

                $service = $serviceQuery->first() ?? new CommercialService([
                    'commercial_client_id' => $client->id,
                    'portfolio' => $portfolio,
                ]);

                $wasNewService = ! $service->exists;

                $service->fill([
                    'portfolio' => $portfolio,
                    'contract_number' => $contractNumber,
                    'advisor_name' => $this->nullableString($raw['advisor_name'] ?? null),
                    'commercial_sector_id' => $this->resolveCatalogId(CommercialSector::class, $raw['sector'] ?? null),
                    'commercial_client_type_id' => $this->resolveCatalogId(CommercialClientType::class, $raw['client_type'] ?? null),
                    'commercial_service_type_id' => $this->resolveCatalogId(CommercialServiceType::class, $raw['service_type'] ?? null),
                    'service_description' => $this->nullableString($raw['service_description'] ?? null),
                    'contact_name' => $this->nullableString($raw['contact_name'] ?? null),
                    'contact_role' => $this->nullableString($raw['contact_role'] ?? null),
                    'contact_phone' => $this->nullableString($raw['contact_phone'] ?? null),
                    'contact_email' => $this->nullableString($raw['contact_email'] ?? null),
                    'contract_start' => $this->parseDate($raw['contract_start'] ?? null),
                    'contract_end' => $this->parseDate($raw['contract_end'] ?? null),
                    'duration_months' => $this->parseDuration($raw['duration_months'] ?? null),
                    'doc_economic_proposal' => $this->mapDocStatus($raw['doc_economic_proposal'] ?? null),
                    'doc_fo_co_02' => $this->mapDocStatus($raw['doc_fo_co_02'] ?? null),
                    'doc_laft_or_queries' => $this->mapDocStatus($raw['doc_laft_or_queries'] ?? null),
                    'doc_rut' => $this->mapDocStatus($raw['doc_rut'] ?? null),
                    'doc_financials' => $this->mapDocStatus($raw['doc_financials'] ?? null),
                    'doc_legal_rep_id' => $this->mapDocStatus($raw['doc_legal_rep_id'] ?? null),
                    'doc_chamber' => $this->mapDocStatus($raw['doc_chamber'] ?? null),
                    'doc_preinstall' => $this->mapDocStatus($raw['doc_preinstall'] ?? null),
                    'doc_contract' => $this->mapDocStatus($raw['doc_contract'] ?? null),
                    'doc_annex_2' => $this->mapDocStatus($raw['doc_annex_2'] ?? null),
                ]);
                $service->commercial_client_id = $client->id;
                $service->save();

                if ($wasNewService) {
                    $stats['services']++;
                    $count++;
                }
            } catch (Throwable $e) {
                $stats['errors'][] = $sheet->getTitle()." fila {$row}: ".$e->getMessage();
                $stats['skipped']++;
            }
        }

        return $count;
    }

    private function detectHeaderRow(Worksheet $sheet): ?int
    {
        $max = min(10, $sheet->getHighestDataRow());
        for ($row = 1; $row <= $max; $row++) {
            $map = $this->mapHeaders($sheet, $row);
            if (isset($map['nit'], $map['client_name'])) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, int> field => column index (1-based)
     */
    private function mapHeaders(Worksheet $sheet, int $row): array
    {
        $map = [];
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn($row));

        for ($col = 1; $col <= $highestCol; $col++) {
            $raw = $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getValue();
            if ($raw === null || $raw === '') {
                continue;
            }

            $key = $this->normalizeHeader((string) $raw);
            if ($key === 'contrato' && isset($map['doc_contract']) && ! isset($map['contract_number'])) {
                // Segunda columna "CONTRATO" en monitoreo se ignora como checklist duplicado.
                continue;
            }

            $field = self::HEADER_ALIASES[$key] ?? null;
            if ($field && ! isset($map[$field])) {
                $map[$field] = $col;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $map
     * @return array<string, mixed>
     */
    private function rowValues(Worksheet $sheet, int $row, array $map): array
    {
        $values = [];
        foreach ($map as $field => $col) {
            $values[$field] = $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getValue();
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function isEmptyRow(array $raw): bool
    {
        foreach ($raw as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n',
        ]);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        if ($string === '' || str_starts_with($string, '=')) {
            return null;
        }

        return mb_substr($string, 0, 2000);
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $date = ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } else {
                $string = trim((string) $value);
                if ($string === '') {
                    return null;
                }

                $date = date('Y-m-d', strtotime($string));
            }

            return $this->isInvalidImportedDate($date) ? null : $date;
        } catch (Throwable) {
            return null;
        }
    }

    private function isInvalidImportedDate(string $date): bool
    {
        $year = (int) substr($date, 0, 4);

        return $year > 0 && $year < 1980;
    }

    private function parseDuration(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $parsed = null;

        if (is_numeric($value)) {
            $parsed = (int) round((float) $value);
        } elseif (preg_match('/(\d+)/', (string) $value, $matches)) {
            $parsed = (int) $matches[1];
        }

        if ($parsed === null || $parsed < 0 || $parsed > 600) {
            return null;
        }

        return $parsed;
    }

    private function mapDocStatus(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = mb_strtoupper(trim((string) $value));

        return match (true) {
            in_array($raw, ['OK', 'SI', 'SÍ', 'P'], true) => CommercialService::DOC_OK,
            in_array($raw, ['X', 'NO'], true) => CommercialService::DOC_X,
            in_array($raw, ['O', 'N/A', 'NA'], true) => CommercialService::DOC_NA,
            in_array($raw, ['I', 'INC'], true) => CommercialService::DOC_INCOMPLETE,
            default => CommercialService::DOC_PENDING,
        };
    }

    /**
     * @param  class-string<CommercialSector|CommercialClientType|CommercialServiceType>  $modelClass
     */
    private function resolveCatalogId(string $modelClass, mixed $value): ?int
    {
        $name = $this->nullableString($value);
        if ($name === null) {
            return null;
        }

        $name = mb_strtoupper($name);

        $model = $modelClass::query()->firstOrCreate(
            ['name' => $name],
            ['is_active' => true, 'sort_order' => 999]
        );

        return $model->id;
    }
}
