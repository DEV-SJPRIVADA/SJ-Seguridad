<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BaseExport
{
    protected Collection $data;
    protected array $columns;
    protected string $fileName;
    protected string $title;

    public function __construct(Collection $data, array $columns, string $fileName, string $title = '')
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->fileName = $fileName;
        $this->title = $title;
    }

    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;

        if ($this->title) {
            $sheet->setCellValue('A1', $this->title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->mergeCells('A1:' . $this->columnLetter(count($this->columns) - 1) . '1');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row = 2;
        }

        $headerRow = $row;
        foreach ($this->columns as $index => $column) {
            $cell = $this->columnLetter($index) . $row;
            $sheet->setCellValue($cell, $column['label']);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF003366');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $row++;

        foreach ($this->data as $dataRow) {
            foreach ($this->columns as $index => $column) {
                $cell = $this->columnLetter($index) . $row;
                $value = $this->extractValue($dataRow, $column);
                $sheet->setCellValue($cell, $value);
            }
            $row++;
        }

        $lastRow = max($row - 1, $headerRow);
        $lastColumn = $this->columnLetter(count($this->columns) - 1);
        $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $lastRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(0, count($this->columns) - 1) as $index) {
            $col = $this->columnLetter($index);
            $sheet->getColumnDimension($col)->setAutoSize(true);
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
        return chr(ord('A') + $index);
    }

    private function extractValue($dataRow, array $column): mixed
    {
        $key = $column['key'] ?? null;

        if ($key instanceof \Closure) {
            return $key($dataRow);
        }

        if (is_string($key)) {
            return data_get($dataRow, $key, '');
        }

        return '';
    }
}
