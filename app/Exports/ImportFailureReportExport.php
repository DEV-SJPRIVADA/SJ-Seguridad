<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportFailureReportExport
{
    /**
     * @param  list<array<string, mixed>>  $failures
     * @param  array<string, int|string>  $summary
     */
    public function __construct(
        private readonly array $failures,
        private readonly array $summary,
        private readonly string $moduleTitle,
        private readonly array $rawColumnKeys,
    ) {}

    public function saveToPath(string $path): void
    {
        $spreadsheet = $this->buildSpreadsheet();
        (new Xlsx($spreadsheet))->save($path);
    }

    public function download(string $fileName): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet();

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $this->writeSummarySheet($spreadsheet->getActiveSheet());
        $this->writeFailuresSheet($spreadsheet->createSheet());

        return $spreadsheet;
    }

    private function writeSummarySheet(Worksheet $sheet): void
    {
        $sheet->setTitle('Resumen');
        $sheet->setCellValue('A1', 'Reporte de filas fallidas — '.$this->moduleTitle);
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $row = 3;
        foreach ($this->summary as $label => $value) {
            $sheet->setCellValue('A'.$row, (string) $label);
            $sheet->setCellValue('B'.$row, (string) $value);
            $row++;
        }
    }

    private function writeFailuresSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('Filas fallidas');

        $headers = [
            'Fila',
            'Identificador',
            'Tipo',
            'Motivo',
        ];

        foreach ($this->rawColumnKeys as $key) {
            $headers[] = $key;
        }

        foreach ($headers as $index => $label) {
            $col = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($col.'1', $label);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($this->failures as $failure) {
            $colIndex = 1;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++).$row, (int) ($failure['row'] ?? 0));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++).$row, (string) ($failure['identifier'] ?? ''));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++).$row, $this->severityLabel((string) ($failure['severity'] ?? '')));
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++).$row, (string) ($failure['reason'] ?? ''));

            $raw = is_array($failure['raw'] ?? null) ? $failure['raw'] : [];
            foreach ($this->rawColumnKeys as $key) {
                $value = $raw[$key] ?? '';
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($colIndex++).$row, is_scalar($value) ? (string) $value : '');
            }

            $row++;
        }

        $sheet->freezePane('A2');
    }

    private function severityLabel(string $severity): string
    {
        return match ($severity) {
            'error' => 'Error',
            'skipped' => 'Omitida',
            'empty' => 'Vacia',
            default => $severity,
        };
    }
}
