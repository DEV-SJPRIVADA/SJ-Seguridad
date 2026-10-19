<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plantilla de carga masiva de proveedores VINE01.
 *
 * Hoja 1 "VINE01": fila 1 con los encabezados oficiales; datos desde fila 2.
 * Hoja 2 "Instrucciones": descripción de cada columna.
 */
class PurchaseProviderImportTemplateExport
{
    public function download(string $fileName = 'plantilla_proveedores_VINE01.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;

        $this->writeDataSheet($spreadsheet->getActiveSheet());
        $this->writeInstructionsSheet($spreadsheet->createSheet());

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function writeDataSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('VINE01');

        /** @var array<string, string> $columns */
        $columns = config('purchase-requests.providers_import_columns', []);
        $colIndex = 1;

        foreach (array_keys($columns) as $header) {
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($col.'1', $header);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $colIndex++;
        }

        $sheet->freezePane('A2');
    }

    private function writeInstructionsSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('Instrucciones');
        $sheet->setCellValue('A1', 'Columna');
        $sheet->setCellValue('B1', 'Descripción');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        /** @var array<string, string> $columns */
        $columns = config('purchase-requests.providers_import_columns', []);
        $row = 2;

        foreach ($columns as $header => $description) {
            $sheet->setCellValue('A'.$row, $header);
            $sheet->setCellValue('B'.$row, $description);
            $row++;
        }

        $sheet->setCellValue('A'.$row, 'Nota');
        $sheet->setCellValue('B'.$row, 'Solo se cargan las filas con PROVEEDOR = SI. Los datos se actualizan por IDENTIFICA.');
        $sheet->getColumnDimension('B')->setWidth(70);
    }
}
