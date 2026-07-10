<?php

namespace App\Services\Indicadores;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndicatorReportExporter
{
    public function leaderExcelResponse(array $report): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Captura');

        $indicator = $report['indicator'];
        $leader = $report['operations_leader'];
        $year = $report['year'];
        $month = $report['month'];
        $capture = $report['capture'] ?? null;

        $this->writeHeaderBlock($sheet, 'Reporte por jefe de operaciones', [
            'Indicador' => $indicator->code.' - '.$indicator->name,
            'Jefe' => $leader->code.' - '.$leader->name,
            'Periodo' => sprintf('%d-%02d', $year, $month),
        ]);

        $row = 6;
        $sheet->setCellValue('A'.$row, 'Campo');
        $sheet->setCellValue('B'.$row, 'Valor');
        $this->styleTableHeader($sheet, 'A'.$row.':B'.$row);
        $row++;

        foreach ($report['display'] as $key => $value) {
            $sheet->setCellValue('A'.$row, (string) $key);
            $sheet->setCellValue('B'.$row, is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value);
            $row++;
        }

        $rows = [
            ['Resultado %', $capture?->result_percentage],
            ['Semaforo', $capture ? ($capture->complies ? 'VERDE' : 'ROJO') : '-'],
            ['Analisis', $capture?->analysis_text],
            ['Mejora - Analisis', $capture?->improvement?->analysis],
            ['Mejora - Accion tomada', $capture?->improvement?->action_taken],
            ['Mejora - Accion definida', $capture?->improvement?->action_defined],
        ];

        foreach ($rows as [$label, $value]) {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->setCellValue('B'.$row, (string) ($value ?? ''));
            $row++;
        }

        $lastRow = max($row - 1, 6);
        $sheet->getStyle('A6:B'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(48);

        $filename = sprintf(
            'jefe-%s-%s-%d-%02d.xlsx',
            $indicator->code,
            $leader->code,
            $year,
            $month
        );

        return $this->streamDownload($spreadsheet, $filename);
    }

    public function motherExcelResponse(array $report): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MADRE');

        $indicator = $report['indicator'];
        $year = $report['year'];
        $month = $report['month'];
        $monthly = $report['monthly'];

        $this->writeHeaderBlock($sheet, 'Consolidado MADRE', [
            'Indicador' => $indicator->code.' - '.$indicator->name,
            'Periodo' => sprintf('%d-%02d', $year, $month),
        ]);

        $row = 5;
        $headers = ['Jefe', 'Numerador', 'Denominador', '%', 'Semaforo'];
        foreach ($headers as $index => $header) {
            $column = chr(ord('A') + $index);
            $sheet->setCellValue($column.$row, $header);
        }
        $this->styleTableHeader($sheet, 'A'.$row.':E'.$row);
        $row++;

        foreach ($monthly['rows'] as $leaderRow) {
            $capture = $leaderRow['capture'] ?? null;
            $sheet->setCellValue('A'.$row, $leaderRow['operations_leader']->code.' - '.$leaderRow['operations_leader']->name);
            $sheet->setCellValue('B'.$row, $capture?->numerator);
            $sheet->setCellValue('C'.$row, $capture?->denominator);
            $sheet->setCellValue('D'.$row, $leaderRow['result_percentage']);
            $sheet->setCellValue('E'.$row, $leaderRow['semaforo']);
            $row++;
        }

        $consolidated = $monthly['consolidated'] ?? [];
        $row++;
        $sheet->setCellValue('A'.$row, 'Consolidado');
        $sheet->setCellValue('D'.$row, $consolidated['result_percentage'] ?? null);
        $sheet->setCellValue('E'.$row, $consolidated['semaforo'] ?? '-');
        $sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true);

        $lastRow = max($row, 5);
        $sheet->getStyle('A5:E'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf('madre-%s-%d-%02d.xlsx', $indicator->code, $year, $month);

        return $this->streamDownload($spreadsheet, $filename);
    }

    /**
     * @param  array<string, string>  $lines
     */
    private function writeHeaderBlock(Worksheet $sheet, string $title, array $lines): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 2;
        foreach ($lines as $label => $value) {
            $sheet->setCellValue('A'.$row, $label);
            $sheet->setCellValue('B'.$row, $value);
            $row++;
        }
    }

    private function styleTableHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF003366');
        $sheet->getStyle($range)->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function streamDownload(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
