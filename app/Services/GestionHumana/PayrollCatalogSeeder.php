<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
use App\Support\SpreadsheetCellReader;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollCatalogSeeder
{
    /**
     * @return array<string, int>
     */
    public function seedFromDirectory(string $directory, bool $dryRun = false): array
    {
        $stats = [];
        $files = [
            'activos' => $this->findFile($directory, 'ACTIVOS'),
            'empleados' => $directory.DIRECTORY_SEPARATOR.'EMPLEADOS.xlsx',
        ];

        foreach ($files as $label => $path) {
            if ($path === null || ! is_readable($path)) {
                continue;
            }

            $fileStats = $this->seedFromSpreadsheet($path, $dryRun);
            foreach ($fileStats as $type => $count) {
                $stats[$type] = ($stats[$type] ?? 0) + $count;
            }
        }

        return $stats;
    }

    private function findFile(string $directory, string $prefix): ?string
    {
        foreach (glob($directory.DIRECTORY_SEPARATOR.$prefix.'*') ?: [] as $file) {
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    private function seedFromSpreadsheet(string $path, bool $dryRun): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $headers = $this->headers($sheet);
        $stats = [];
        $maxRow = min((int) $sheet->getHighestRow(), 5000);

        for ($row = 2; $row <= $maxRow; $row++) {
            $rowData = $this->row($sheet, $row, $headers);
            if (trim((string) ($rowData['cedula'] ?? '')) === '') {
                continue;
            }

            $pairs = [
                ['document_type', $rowData['tipo_documento'] ?? null, $rowData['tipo_documento'] ?? null],
                ['city', $rowData['codigo_lugar_residencia'] ?? null, $rowData['lugar_residencia'] ?? null],
                ['position', $rowData['cargo'] ?? null, $rowData['nombre_cargo'] ?? null],
                ['cost_center', $rowData['ccosto'] ?? null, $rowData['nombre_ccosto'] ?? null],
                ['eps', $rowData['codigo_eps'] ?? null, $rowData['nombre_eps'] ?? null],
                ['afp', $rowData['codigo_afp'] ?? null, $rowData['nombre_afp'] ?? null],
                ['arp', null, $rowData['nombre_arp'] ?? null],
                ['bank', $rowData['banco'] ?? null, $rowData['banco'] ?? null],
                ['payment_method', $rowData['forma_pago'] ?? null, $rowData['forma_pago'] ?? null],
                ['contract_type', $rowData['tipo_contrato'] ?? null, $rowData['tipo_contrato'] ?? null],
                ['salary_type', $rowData['tipo_salario'] ?? null, $rowData['tipo_salario'] ?? null],
                ['economic_activity', $rowData['actividad_economica'] ?? null, $rowData['nombre_actividad_economica'] ?? null],
            ];

            foreach ($pairs as [$type, $code, $name]) {
                if ($dryRun) {
                    if (trim((string) $code) !== '' || trim((string) $name) !== '') {
                        $stats[$type] = ($stats[$type] ?? 0) + 1;
                    }

                    continue;
                }

                $item = PayrollCatalogItem::upsertPair($type, $code !== null ? (string) $code : null, $name !== null ? (string) $name : null);
                if ($item !== null) {
                    $stats[$type] = ($stats[$type] ?? 0) + 1;
                }
            }
        }

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    private function headers(Worksheet $sheet): array
    {
        $headers = [];
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($col = 1; $col <= $maxCol; $col++) {
            $key = mb_strtolower(trim((string) SpreadsheetCellReader::rawValue($sheet, $col, 1)));
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
    private function row(Worksheet $sheet, int $row, array $headers): array
    {
        $data = [];
        foreach ($headers as $key => $col) {
            $data[$key] = SpreadsheetCellReader::value($sheet, $col, $row);
        }

        return $data;
    }
}
