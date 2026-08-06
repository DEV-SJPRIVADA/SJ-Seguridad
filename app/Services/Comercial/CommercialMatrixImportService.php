<?php

namespace App\Services\Comercial;

use App\Support\ImportFailureRow;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CommercialMatrixImportService
{
    public function __construct(
        private readonly CommercialMatrixRowProcessor $rowProcessor,
    ) {}

    /**
     * @return array{
     *     clients_created: int,
     *     clients_updated: int,
     *     services_created: int,
     *     services_updated: int,
     *     skipped: int,
     *     empty_rows: int,
     *     errors: list<string>,
     *     failures: list<array<string, mixed>>
     * }
     */
    public function import(string $path, ?int $userId = null): array
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("No se encontro el archivo: {$path}");
        }

        ini_set('memory_limit', '1024M');

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($path);

        return DB::transaction(function () use ($spreadsheet, $userId): array {
            $stats = [
                'clients_created' => 0,
                'clients_updated' => 0,
                'services_created' => 0,
                'services_updated' => 0,
                'skipped' => 0,
                'empty_rows' => 0,
                'errors' => [],
                'failures' => [],
            ];

            $clientsCreated = [];
            $clientsUpdated = [];

            $sheetName = config('commercial_matrix.sheet_name', 'Matriz comercial');
            $sheet = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getSheet(0);

            $columnMap = $this->mapColumnsFromKeyRow($sheet);
            if (! isset($columnMap['nit'], $columnMap['client_name'])) {
                throw new \InvalidArgumentException('La plantilla no contiene las columnas obligatorias nit y client_name en la fila 1.');
            }

            $startRow = (int) config('commercial_matrix.data_start_row', 3);
            $highestRow = $sheet->getHighestDataRow();

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $raw = $this->rowValues($sheet, $row, $columnMap);
                $result = $this->rowProcessor->processRow($raw, $row, $userId);

                if ($result['failure'] !== null) {
                    $stats['failures'][] = $result['failure'];
                    if (($result['failure']['severity'] ?? '') === ImportFailureRow::SEVERITY_ERROR) {
                        $stats['errors'][] = ImportFailureRow::message($result['failure']);
                    }
                } elseif ($result['error'] !== null) {
                    $stats['errors'][] = $result['error'];
                }

                if ($result['empty_row']) {
                    $stats['empty_rows']++;

                    continue;
                }

                if ($result['skipped']) {
                    $stats['skipped']++;

                    continue;
                }

                if ($result['client_created'] && $result['nit'] !== null && ! isset($clientsCreated[$result['nit']])) {
                    $stats['clients_created']++;
                    $clientsCreated[$result['nit']] = true;
                } elseif ($result['client_updated'] && $result['nit'] !== null && ! isset($clientsUpdated[$result['nit']]) && ! isset($clientsCreated[$result['nit']])) {
                    $stats['clients_updated']++;
                    $clientsUpdated[$result['nit']] = true;
                }

                if ($result['service_created']) {
                    $stats['services_created']++;
                } elseif ($result['service_updated']) {
                    $stats['services_updated']++;
                }
            }

            return $stats;
        });
    }

    /**
     * @return array<string, int>
     */
    private function mapColumnsFromKeyRow(Worksheet $sheet): array
    {
        $keyRow = (int) config('commercial_matrix.header_key_row', 1);
        $allowedKeys = array_keys(config('commercial_matrix.import_columns', []));
        $map = [];
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn($keyRow));

        for ($col = 1; $col <= $highestCol; $col++) {
            $raw = $sheet->getCell(Coordinate::stringFromColumnIndex($col).$keyRow)->getValue();
            if ($raw === null || $raw === '') {
                continue;
            }

            $key = trim((string) $raw);
            if (in_array($key, $allowedKeys, true) && ! isset($map[$key])) {
                $map[$key] = $col;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $columnMap
     * @return array<string, mixed>
     */
    private function rowValues(Worksheet $sheet, int $row, array $columnMap): array
    {
        $values = [];
        foreach ($columnMap as $field => $col) {
            $values[$field] = $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row)->getValue();
        }

        return $values;
    }
}
