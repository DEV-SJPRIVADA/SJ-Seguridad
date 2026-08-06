<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Support\ImportFailureRow;
use App\Support\SpreadsheetCellReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeFichaImportService
{
    /**
     * @return array{imported: int, updated: int, skipped: int, empty_rows: int, errors: list<string>, failures: list<array<string, mixed>>}
     */
    public function import(string $path, bool $dryRun = false, ?int $userId = null): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException('No se puede leer el archivo: '.$path);
        }

        $stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'empty_rows' => 0, 'errors' => [], 'failures' => []];
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $this->readHeaders($sheet);

        if ($headers === []) {
            throw new \RuntimeException('El archivo no tiene encabezados validos en la fila 1.');
        }

        $maxRow = (int) $sheet->getHighestRow();

        for ($row = 3; $row <= $maxRow; $row++) {
            $data = $this->readRow($sheet, $row, $headers);
            $cedula = trim((string) ($data['cedula'] ?? ''));

            if ($cedula === '') {
                $stats['empty_rows']++;
                $stats['failures'][] = ImportFailureRow::make(
                    $row,
                    null,
                    'Cedula',
                    ImportFailureRow::SEVERITY_EMPTY,
                    'Fila sin cedula (ignorada).',
                    $data,
                );

                continue;
            }

            try {
                if ($dryRun) {
                    $stats['imported']++;

                    continue;
                }

                DB::transaction(function () use ($data, $cedula, $userId, &$stats): void {
                    $existing = EmployeeFichaProfile::query()->where('document_number', $cedula)->first();
                    $payload = $this->mapImportPayload($data, $cedula);
                    $entry = $this->resolveFichaEntry($cedula, $data, $userId);

                    if ($entry !== null) {
                        $payload['personal_requisition_ficha_entry_id'] = $entry->id;
                    }

                    if ($existing !== null) {
                        $existing->update($payload);
                        $stats['updated']++;
                    } else {
                        EmployeeFichaProfile::query()->create($payload);
                        $stats['imported']++;
                    }
                });
            } catch (\Throwable $e) {
                $failure = ImportFailureRow::make(
                    $row,
                    $cedula,
                    'Cedula',
                    ImportFailureRow::SEVERITY_ERROR,
                    $e->getMessage(),
                    $data,
                );
                $stats['failures'][] = $failure;
                $stats['errors'][] = ImportFailureRow::message($failure);
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    private function readHeaders(Worksheet $sheet): array
    {
        $headers = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($col = 1; $col <= $maxCol; $col++) {
            $key = trim((string) SpreadsheetCellReader::rawValue($sheet, $col, 1));
            if ($key !== '') {
                $headers[$key] = $col;
            }
        }

        return $headers;
    }

    /**
     * @param  array<string, int>  $headers
     * @return array<string, mixed>
     */
    private function readRow(Worksheet $sheet, int $row, array $headers): array
    {
        $data = [];
        foreach ($headers as $key => $col) {
            $data[$key] = SpreadsheetCellReader::value($sheet, $col, $row);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapImportPayload(array $data, string $cedula): array
    {
        $parsed = EmployeeFichaNameParser::parse(trim((string) ($data['nombre'] ?? '')));
        $terminationDate = $this->parseDate($data['fecha_retiro'] ?? null);

        $payload = [
            'document_number' => $cedula,
            'full_name' => $parsed['full_name'] ?: trim((string) ($data['nombre'] ?? '')),
            'first_surname' => $parsed['first_surname'],
            'second_surname' => $parsed['second_surname'],
            'first_name' => $parsed['first_name'],
            'second_name' => $parsed['second_name'],
            'document_type' => $this->stringOrNull($data['tipo_documento'] ?? null),
            'birth_date' => $this->parseDate($data['fecha_nac'] ?? null),
            'expedition_city_code' => $this->stringOrNull($data['codigo_lugar_exp_cedula'] ?? null),
            'expedition_city_name' => $this->stringOrNull($data['lugar_exp_cedula'] ?? null),
            'expedition_date' => $this->parseDate($data['fecha_expedicion'] ?? null),
            'residence_city_code' => $this->stringOrNull($data['codigo_lugar_residencia'] ?? null),
            'residence_city_name' => $this->stringOrNull($data['lugar_residencia'] ?? null),
            'address' => $this->stringOrNull($data['direccion'] ?? null),
            'phone' => $this->stringOrNull($data['telefono'] ?? null),
            'blood_type' => $this->stringOrNull($data['tipo_sangre'] ?? null),
            'sex' => $this->normalizeSex($data['sexo'] ?? null),
            'salary' => $this->numericOrNull($data['salario'] ?? null),
            'education_level' => $this->stringOrNull($data['escolaridad'] ?? null),
            'marital_status' => $this->stringOrNull($data['estado_civil'] ?? null),
            'children_count' => $this->intOrNull($data['numero_hijos'] ?? null),
            'email' => $this->stringOrNull($data['email'] ?? null),
            'linkage_type' => $this->stringOrNull($data['tipo_vinculacion'] ?? null),
            'contributor_type' => $this->stringOrNull($data['tipo_cotizante'] ?? null),
            'hire_date' => $this->parseDate($data['fecha_ingreso'] ?? null),
            'contract_end_date' => $this->parseDate($data['fecha_vencimiento_contrato'] ?? null),
            'termination_date' => $terminationDate,
            'work_center_name' => $this->stringOrNull($data['nombre_centro_trabajo'] ?? null),
            'cost_center_code' => $this->stringOrNull($data['ccosto'] ?? null),
            'cost_center_name' => $this->stringOrNull($data['nombre_ccosto'] ?? null),
            'position_code' => $this->stringOrNull($data['cargo'] ?? null),
            'position_name' => $this->stringOrNull($data['nombre_cargo'] ?? null),
            'salary_scale' => $this->stringOrNull($data['escala'] ?? null),
            'salary_type_code' => $this->stringOrNull($data['tipo_salario'] ?? null),
            'contract_type_code' => $this->stringOrNull($data['tipo_contrato'] ?? null),
            'eps_code' => $this->stringOrNull($data['codigo_eps'] ?? null),
            'eps_name' => $this->stringOrNull($data['nombre_eps'] ?? null),
            'afp_code' => $this->stringOrNull($data['codigo_afp'] ?? null),
            'afp_name' => $this->stringOrNull($data['nombre_afp'] ?? null),
            'arp_name' => $this->stringOrNull($data['nombre_arp'] ?? null),
            'risk_level' => $this->stringOrNull($data['nivel_riesgo_arp'] ?? null),
            'compensation_fund_name' => $this->stringOrNull($data['nombre_caja_compensacion'] ?? null),
            'bank_code' => $this->stringOrNull($data['banco'] ?? null),
            'account_type' => $this->stringOrNull($data['tipo_de_cuenta'] ?? null),
            'account_number' => $this->stringOrNull($data['cuenta'] ?? null),
            'payment_method_code' => $this->stringOrNull($data['forma_pago'] ?? null),
            'economic_activity_code' => $this->stringOrNull($data['actividad_economica'] ?? null),
            'economic_activity_name' => $this->stringOrNull($data['nombre_actividad_economica'] ?? null),
        ];

        $profile = new EmployeeFichaProfile($payload);
        $profile->syncEmploymentStatusFromTerminationDate();
        $payload['employment_status'] = $profile->employment_status;

        $this->seedCatalogPairsFromRow($data);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveFichaEntry(string $cedula, array $data, ?int $userId): ?PersonalRequisitionFichaEntry
    {
        $entry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $cedula)
            ->first();

        if ($entry !== null) {
            if ($entry->moved_to_ficha_at === null) {
                $entry->update([
                    'moved_to_ficha_at' => now(),
                    'moved_to_ficha_by' => $userId,
                ]);
            }

            return $entry;
        }

        $requisitionId = null;
        $code = trim((string) ($data['codigo_requisicion'] ?? ''));

        if ($code !== '') {
            $requisitionId = PersonalRequisition::query()->where('code', $code)->value('id');
        }

        return PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisitionId,
            'hired_document' => $cedula,
            'hired_full_name' => trim((string) ($data['nombre'] ?? $cedula)),
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $userId,
            'created_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedCatalogPairsFromRow(array $data): void
    {
        PayrollCatalogItem::upsertPair('document_type', $this->stringOrNull($data['tipo_documento'] ?? null), $this->stringOrNull($data['tipo_documento'] ?? null));
        PayrollCatalogItem::upsertPair('city', $this->stringOrNull($data['codigo_lugar_residencia'] ?? null), $this->stringOrNull($data['lugar_residencia'] ?? null));
        PayrollCatalogItem::upsertPair('position', $this->stringOrNull($data['cargo'] ?? null), $this->stringOrNull($data['nombre_cargo'] ?? null));
        PayrollCatalogItem::upsertPair('cost_center', $this->stringOrNull($data['ccosto'] ?? null), $this->stringOrNull($data['nombre_ccosto'] ?? null));
        PayrollCatalogItem::upsertPair('eps', $this->stringOrNull($data['codigo_eps'] ?? null), $this->stringOrNull($data['nombre_eps'] ?? null));
        PayrollCatalogItem::upsertPair('afp', $this->stringOrNull($data['codigo_afp'] ?? null), $this->stringOrNull($data['nombre_afp'] ?? null));
        PayrollCatalogItem::upsertPair('arp', null, $this->stringOrNull($data['nombre_arp'] ?? null));
        PayrollCatalogItem::upsertPair('bank', $this->stringOrNull($data['banco'] ?? null), $this->stringOrNull($data['banco'] ?? null));
        PayrollCatalogItem::upsertPair('payment_method', $this->stringOrNull($data['forma_pago'] ?? null), $this->stringOrNull($data['forma_pago'] ?? null));
        PayrollCatalogItem::upsertPair('contract_type', $this->stringOrNull($data['tipo_contrato'] ?? null), $this->stringOrNull($data['tipo_contrato'] ?? null));
        PayrollCatalogItem::upsertPair('salary_type', $this->stringOrNull($data['tipo_salario'] ?? null), $this->stringOrNull($data['tipo_salario'] ?? null));
        PayrollCatalogItem::upsertPair('economic_activity', $this->stringOrNull($data['actividad_economica'] ?? null), $this->stringOrNull($data['nombre_actividad_economica'] ?? null));
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^\d,.-]/', '', (string) $value) ?? '';
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeSex(mixed $value): ?string
    {
        $value = mb_strtoupper(trim((string) ($value ?? '')));

        return match (true) {
            str_starts_with($value, 'M') => 'M',
            str_starts_with($value, 'F') => 'F',
            default => $value !== '' ? $value : null,
        };
    }
}
