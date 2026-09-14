<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseRequestItemsImportTemplateExport
{
    public function download(string $fileName = 'plantilla_items_solicitud_compra.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $this->writeHeaders($spreadsheet->getActiveSheet());

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function writeHeaders(Worksheet $sheet): array
    {
        /** @var array<string, string> $columns */
        $columns = config('purchase-requests.items_import_columns', []);
        $colIndex = 1;

        foreach ($columns as $key => $label) {
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($col.'1', $key);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $sheet->setCellValue($col.'2', $label);
            $colIndex++;
        }

        $sheet->freezePane('A3');

        return $columns;
    }
}
