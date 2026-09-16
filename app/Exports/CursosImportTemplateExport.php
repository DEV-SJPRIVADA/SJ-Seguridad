<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CursosImportTemplateExport
{
    public function download(string $fileName = 'plantilla_cursos.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        /** @var array<string, string> $columns */
        $columns = config('cursos.import.columns', []);
        $colIndex = 1;

        foreach ($columns as $key => $label) {
            $col = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($col.'1', $key);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
            $sheet->setCellValue($col.'2', $label);
            $colIndex++;
        }

        $sheet->freezePane('A3');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
