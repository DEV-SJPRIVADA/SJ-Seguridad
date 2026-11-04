<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
        $this->writeTo($spreadsheet->getActiveSheet());

        return $this->streamSpreadsheet($spreadsheet);
    }

    /**
     * Export por chunks (chunkById) sin cargar toda la query en memoria.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): array<string, mixed>  $mapRow
     */
    public static function downloadFromQuery(
        Builder $query,
        array $columns,
        string $fileName,
        string $title,
        callable $mapRow,
        int $chunkSize = 500,
        ?string $chunkColumn = null,
    ): StreamedResponse {
        $chunkSize = max(1, $chunkSize);
        $model = $query->getModel();
        $chunkColumn ??= $model->getQualifiedKeyName();
        $chunkAlias = $model->getKeyName();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $export = new self(collect(), $columns, $fileName, $title);

        $dataStartRow = $export->writeHeaderBlock($sheet);
        $headerRow = $dataStartRow - 1;
        $row = $dataStartRow;

        $chunkQuery = clone $query;
        $chunkQuery->reorder();

        $chunkQuery->chunkById(
            $chunkSize,
            function ($records) use ($export, $sheet, $mapRow, &$row): void {
                foreach ($records as $record) {
                    $mapped = $mapRow($record);
                    $export->writeDataRowAt($sheet, $row, $mapped);
                    $row++;
                }
            },
            $chunkColumn,
            $chunkAlias,
        );

        $export->applySheetChrome($sheet, $headerRow, max($row - 1, $headerRow));

        return $export->streamSpreadsheet($spreadsheet);
    }

    /**
     * Escribe título, encabezados y filas en una hoja. Permite armar libros con varias hojas con el mismo formato.
     */
    public function writeTo(Worksheet $sheet): void
    {
        $dataStartRow = $this->writeHeaderBlock($sheet);
        $row = $dataStartRow;

        foreach ($this->data as $dataRow) {
            $this->writeDataRowAt($sheet, $row, $dataRow);
            $row++;
        }

        $this->applySheetChrome($sheet, $dataStartRow - 1, max($row - 1, $dataStartRow - 1));
    }

    /**
     * @return int Primera fila de datos (debajo de encabezados).
     */
    protected function writeHeaderBlock(Worksheet $sheet): int
    {
        $row = 1;

        if ($this->title) {
            $sheet->setCellValue('A1', $this->title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->mergeCells('A1:'.$this->columnLetter(count($this->columns) - 1).'1');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row = 2;
        }

        $headerRow = $row;
        foreach ($this->columns as $index => $column) {
            $cell = $this->columnLetter($index).$row;
            $sheet->setCellValue($cell, $column['label']);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF003366');
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return $headerRow + 1;
    }

    /**
     * @param  array<string, mixed>|object  $dataRow
     */
    protected function writeDataRowAt(Worksheet $sheet, int $row, array|object $dataRow): void
    {
        foreach ($this->columns as $index => $column) {
            $cell = $this->columnLetter($index).$row;
            $sheet->setCellValue($cell, $this->extractValue($dataRow, $column));
        }
    }

    protected function applySheetChrome(Worksheet $sheet, int $headerRow, int $lastRow): void
    {
        $lastColumn = $this->columnLetter(count($this->columns) - 1);
        $sheet->getStyle('A'.$headerRow.':'.$lastColumn.$lastRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(0, count($this->columns) - 1) as $index) {
            $col = $this->columnLetter($index);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    protected function streamSpreadsheet(Spreadsheet $spreadsheet): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $this->fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function columnLetter(int $index): string
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

    protected function extractValue($dataRow, array $column): mixed
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
