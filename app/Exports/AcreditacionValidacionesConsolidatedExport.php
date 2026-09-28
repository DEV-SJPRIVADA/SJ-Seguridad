<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcreditacionValidacionesConsolidatedExport
{
    /**
     * @param  list<array{title: string, columns: list<array{key: string, label: string}>, data: Collection<int, array<string, mixed>>}>  $sheets
     */
    public function __construct(
        private readonly array $sheets,
        private readonly string $fileName,
        private readonly string $workbookTitle = '',
    ) {}

    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $first = true;

        foreach ($this->sheets as $sheetDef) {
            $sheet = $first ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $first = false;

            $title = mb_substr((string) ($sheetDef['title'] ?? 'Hoja'), 0, 31);
            $sheet->setTitle($title !== '' ? $title : 'Hoja');

            /** @var list<array{key: string, label: string}> $columns */
            $columns = $sheetDef['columns'] ?? [];
            /** @var Collection<int, array<string, mixed>> $data */
            $data = $sheetDef['data'] instanceof Collection
                ? $sheetDef['data']
                : collect($sheetDef['data'] ?? []);

            $row = 1;

            if ($this->workbookTitle !== '') {
                $sheet->setCellValue('A1', $this->workbookTitle.' — '.$title);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
                if (count($columns) > 1) {
                    $sheet->mergeCells('A1:'.$this->columnLetter(count($columns) - 1).'1');
                }
                $row = 2;
            }

            $headerRow = $row;
            foreach ($columns as $index => $column) {
                $cell = $this->columnLetter($index).$row;
                $sheet->setCellValue($cell, $column['label']);
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF003366');
                $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $row++;

            foreach ($data as $dataRow) {
                foreach ($columns as $index => $column) {
                    $cell = $this->columnLetter($index).$row;
                    $sheet->setCellValue($cell, data_get($dataRow, $column['key'] ?? '', ''));
                }
                $row++;
            }

            if (count($columns) > 0) {
                $lastRow = max($row - 1, $headerRow);
                $lastColumn = $this->columnLetter(count($columns) - 1);
                $sheet->getStyle('A'.$headerRow.':'.$lastColumn.$lastRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                foreach (range(0, count($columns) - 1) as $index) {
                    $col = $this->columnLetter($index);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $this->fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index++;

        while ($index > 0) {
            $index--;
            $letter = chr(ord('A') + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
