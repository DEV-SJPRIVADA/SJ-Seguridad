<?php

namespace App\Services\Indicadores;

use App\Models\Indicator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IndicatorCaptureExcelExporter
{
    private Worksheet $sheet;

    private int $row = 1;

    /**
     * @param  array<string, mixed>  $context
     */
    public function download(array $context, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle(substr((string) ($context['indicator']->code ?? 'Indicador'), 0, 31));
        $this->row = 1;

        $this->writeMeta($context);
        $this->writeFields($context);
        $this->writeMetrics($context);
        $this->writeImprovement($context);

        if (($context['indicator']->code ?? '') === 'FT-OP-03') {
            $this->writeFtOp03Ficha($context);
            $this->applyColumnWidths('B', 'M', 12);
            $this->sheet->getColumnDimension('A')->setWidth(32);
            $this->sheet->getColumnDimension('N')->setWidth(12);
        } else {
            $this->writeStandardFicha($context);
            $this->applyColumnWidths('A', 'B', 14);
            $this->applyColumnWidths('C', 'L', 12);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeMeta(array $context): void
    {
        /** @var Indicator $indicator */
        $indicator = $context['indicator'];
        $months = (array) ($context['months'] ?? config('indicators.months'));
        $selectedYear = (int) ($context['selectedYear'] ?? $context['year'] ?? now()->year);
        $selectedMonth = (int) ($context['selectedMonth'] ?? $context['month'] ?? now()->month);
        $periodLabel = ($months[$selectedMonth] ?? (string) $selectedMonth).' '.$selectedYear;

        $title = ($context['isConsolidadoView'] ?? false) ? 'Consolidado' : 'Captura';
        $this->sheet->setCellValue('A'.$this->row, $title.' — '.$indicator->code);
        $this->sheet->getStyle('A'.$this->row)->getFont()->setBold(true)->setSize(14);
        $this->row++;

        $lines = [
            'Indicador' => $indicator->code.' — '.$indicator->name,
            'Periodo' => $periodLabel,
            'Capturador' => (string) ($context['captureUserName'] ?? ($context['user']->name ?? 'N/A')),
        ];

        if ($context['isConsolidadoView'] ?? false) {
            $lines['Vista'] = empty($context['exportUserId'])
                ? 'Todos los capturadores (consolidado)'
                : 'Captura individual';
        }

        foreach ($lines as $label => $value) {
            $this->sheet->setCellValue('A'.$this->row, $label);
            $this->sheet->setCellValue('B'.$this->row, $value);
            $this->row++;
        }

        $this->row++;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeFields(array $context): void
    {
        $rows = (array) ($context['formFieldRows'] ?? []);
        if ($rows === []) {
            return;
        }

        $this->sheet->setCellValue('A'.$this->row, $context['indicator']->code.' — '.$context['indicator']->name);
        $this->sheet->getStyle('A'.$this->row)->getFont()->setBold(true);
        $this->row++;

        $headerRow = $this->row;
        $this->sheet->setCellValue('A'.$this->row, 'Campo');
        $this->sheet->setCellValue('B'.$this->row, 'Valor');
        $this->styleTableHeader('A'.$this->row.':B'.$this->row);
        $this->row++;

        foreach ($rows as $fieldRow) {
            $this->sheet->setCellValue('A'.$this->row, $fieldRow['label']);
            $this->sheet->setCellValue('B'.$this->row, $fieldRow['value']);
            $this->row++;
        }

        $this->borderRange('A'.$headerRow.':B'.($this->row - 1));
        $this->row++;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeMetrics(array $context): void
    {
        $headerRow = $this->row;
        $metrics = [
            'Resultado %' => number_format((float) $context['resultPercentage'], 2).'%',
            'Semaforo' => (string) $context['semaforo'],
            'Cumple' => ($context['complies'] ?? false) ? 'SI' : 'NO',
            'Mejora' => ($context['improvementId'] ?? null) ? 'SI' : 'NO',
        ];

        $col = 0;
        foreach ($metrics as $label => $value) {
            $letter = $this->columnLetter($col);
            $this->sheet->setCellValue($letter.$this->row, $label);
            $this->sheet->setCellValue($letter.($this->row + 1), $value);
            $this->styleTableHeader($letter.$this->row);
            $col++;
        }

        $this->borderRange('A'.$headerRow.':D'.($this->row + 1));
        $this->row += 3;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeImprovement(array $context): void
    {
        $blocks = array_filter([
            'Analisis captura' => trim((string) ($context['analysisText'] ?? '')),
            'Analisis mejora' => trim((string) ($context['improvementAnalysis'] ?? '')),
            'Accion tomada' => trim((string) ($context['improvementActionTaken'] ?? '')),
            'Accion definida' => trim((string) ($context['improvementActionDefined'] ?? '')),
            'Mejora requerida' => trim((string) ($context['improvementRequired'] ?? '')),
        ]);

        if ($blocks === [] && ! ($context['improvementId'] ?? null)) {
            return;
        }

        $this->sheet->setCellValue('A'.$this->row, 'Analisis y mejora');
        $this->sheet->getStyle('A'.$this->row)->getFont()->setBold(true);
        $this->row++;

        foreach ($blocks as $label => $value) {
            $this->sheet->setCellValue('A'.$this->row, $label);
            $this->sheet->setCellValue('B'.$this->row, $value);
            $this->row++;
        }

        $this->row++;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeStandardFicha(array $context): void
    {
        /** @var Indicator $indicator */
        $indicator = $context['indicator'];
        $months = (array) ($context['months'] ?? config('indicators.months'));
        $selectedYear = (int) ($context['selectedYear'] ?? $context['year']);
        $selectedMonth = (int) ($context['selectedMonth'] ?? $context['month']);
        $sheetRows = (array) ($context['sheetRows'] ?? []);
        $lastCol = 'L';
        $fichaStartRow = $this->row;

        $this->sheet->mergeCells('A'.$this->row.':B'.($this->row + 3));
        $this->embedLogo('A'.$this->row);
        $this->sheet->mergeCells('C'.$this->row.':I'.($this->row + 3));
        $this->sheet->setCellValue('C'.$this->row, 'FICHA DEL INDICADOR DE GESTION');
        $this->sheet->getStyle('C'.$this->row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $this->sheet->getStyle('C'.$this->row)->getFont()->setBold(true);

        $meta = [
            $indicator->code,
            ($months[$selectedMonth] ?? 'Mes').' de '.$selectedYear,
            'Version 02',
            'Pagina 1 de 1',
        ];

        foreach ($meta as $index => $value) {
            $this->sheet->mergeCells('J'.($this->row + $index).':L'.($this->row + $index));
            $this->sheet->setCellValue('J'.($this->row + $index), $value);
        }

        $this->borderRange('A'.$this->row.':L'.($this->row + 3));
        $this->row += 4;

        $this->writeMergedRow('NOMBRE DEL INDICADOR', 'A', $lastCol, head: true);
        $this->writeMergedRow($indicator->name, 'A', $lastCol, center: true, bold: true);
        $this->writeSplitRow('OBJETIVO', 'Medir el grado de cumplimiento del indicador.', 'PROCESO', 'Gestion Operativa', 'A', 'H', 'I', $lastCol);

        $tendency = match ($indicator->target_operator ?? '>=') {
            '<=' => 'Decreciente',
            '==' => 'Objetivo exacto',
            default => 'Creciente',
        };

        $this->writeHeaderRow(['UNIDAD MEDIDA', 'META', 'FRECUENCIA DE MEDICION', 'TENDENCIA', 'INSUMOS PARA LA MEDICION'], [2, 1, 3, 2, 4]);
        $this->writeDataRow([
            ucfirst((string) ($indicator->unit ?? 'Porcentaje')),
            number_format((float) $indicator->target_value, 0).'%',
            ucfirst($indicator->frequency ?? 'Mensual'),
            $tendency,
            'Base de datos del indicador',
        ], [2, 1, 3, 2, 4], center: true);

        $this->writeHeaderRow(['CRITICO', number_format((float) ($indicator->critical_value ?? 0), 0).'%'], [2, 10]);
        $this->writeMergedRow('FORMULA', 'A', $lastCol, head: true);
        $this->writeMergedRow('('.$indicator->formula_description.')', 'A', $lastCol, center: true);

        $this->writeMergedRow('RESPONSABILIDADES', 'A', $lastCol, head: true);
        $this->writeHeaderRow(['RESULTADOS Y MEDICION', 'RESULTADOS', 'MEDICION'], [4, 4, 4]);
        $this->writeDataRow(['Lider de Gestion Operativa', 'N.A.', 'N.A.'], [4, 4, 4], center: true);

        $this->writeMergedRow('RESULTADOS', 'A', $lastCol, head: true);
        $this->writeMonthHeaderRow($sheetRows);
        $this->writeMergedRow((string) $selectedYear, 'A', $lastCol, head: true, center: true);
        $this->writeMergedRow((string) $context['sheetDenominatorLabel'], 'A', $lastCol, head: true, center: true, fill: 'dbeafe');
        $this->writeMonthValues($sheetRows, 'denominator');
        $this->writeMergedRow((string) $context['sheetNumeratorLabel'], 'A', $lastCol, head: true, center: true, fill: 'dbeafe');
        $this->writeMonthValues($sheetRows, 'numerator');
        $this->writeMergedRow('NIVEL DE CUMPLIMIENTO '.strtoupper($indicator->name), 'A', $lastCol, head: true, center: true, fill: 'dbeafe');
        $this->writeMonthPercentages($sheetRows);

        $metaRow = array_fill(0, 12, $indicator->target_operator.' '.number_format((float) $indicator->target_value, 0).'%');
        $this->writeFixedMonthRow($metaRow, head: true);
        $criticalRow = array_fill(0, 12, 'CRITICO '.number_format((float) ($indicator->critical_value ?? 0), 0).'%');
        $this->writeFixedMonthRow($criticalRow, head: true);

        $this->borderRange('A'.$fichaStartRow.':L'.($this->row - 1));

        $this->writeMergedRow('GRAFICOS', 'A', $lastCol, head: true, center: true);
        $this->row++;
        $this->embedChartImage((string) ($context['chartImages']['main'] ?? ''), 'A', $this->row, 300);
        $this->row += 20;

        $this->writeAnalysisTable($context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeFtOp03Ficha(array $context): void
    {
        /** @var Indicator $indicator */
        $indicator = $context['indicator'];
        $months = (array) ($context['months'] ?? config('indicators.months'));
        $selectedYear = (int) ($context['selectedYear'] ?? $context['year']);
        $selectedMonth = (int) ($context['selectedMonth'] ?? $context['month']);
        $lastCol = 'N';
        $monthLabels = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
        $fichaStartRow = $this->row;

        $this->sheet->mergeCells('A'.$this->row.':B'.($this->row + 3));
        $this->embedLogo('A'.$this->row);
        $this->sheet->mergeCells('C'.$this->row.':K'.($this->row + 3));
        $this->sheet->setCellValue('C'.$this->row, 'FICHA DEL INDICADOR DE GESTION');
        $this->sheet->getStyle('C'.$this->row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $this->sheet->getStyle('C'.$this->row)->getFont()->setBold(true);

        $meta = [
            $indicator->code,
            ($months[$selectedMonth] ?? 'Mes').' de '.$selectedYear,
            'Version 02',
            'Pagina 1 de 1',
        ];

        foreach ($meta as $index => $value) {
            $this->sheet->mergeCells('L'.($this->row + $index).':N'.($this->row + $index));
            $this->sheet->setCellValue('L'.($this->row + $index), $value);
        }

        $this->borderRange('A'.$this->row.':N'.($this->row + 3));
        $this->row += 4;

        $this->writeMergedRow('NOMBRE DEL INDICADOR', 'A', $lastCol, head: true);
        $this->writeMergedRow(strtoupper($indicator->name), 'A', $lastCol, center: true, bold: true);
        $this->writeSplitRow(
            'OBJETIVO',
            'Determinar el impacto de los siniestros o reclamos en la facturacion de la empresa.',
            'PROCESO',
            'Operaciones y Gestion de Riesgos',
            'A',
            'I',
            'J',
            $lastCol,
        );

        $tendency = match ($indicator->target_operator ?? '>=') {
            '<=' => 'Decreciente',
            '==' => 'Objetivo exacto',
            default => 'Creciente',
        };

        $this->writeHeaderRow(['UNIDAD MEDIDA', 'META', 'FRECUENCIA DE MEDICION', 'TENDENCIA', 'INSUMOS PARA LA MEDICION'], [3, 1, 3, 2, 5], $lastCol);
        $this->writeDataRow([
            ucfirst((string) ($indicator->unit ?? 'Porcentaje')),
            number_format((float) $indicator->target_value, 0).'%',
            ucfirst($indicator->frequency ?? 'Mensual'),
            $tendency,
            'FO-GI-06 Control de No Conformidades / Reporte clientes',
        ], [3, 1, 3, 2, 5], center: true, lastCol: $lastCol);
        $this->writeHeaderRow(['CRITICO', number_format((float) ($indicator->critical_value ?? 0), 0).'%'], [3, 11], $lastCol);
        $this->writeMergedRow('FORMULA', 'A', $lastCol, head: true);
        $this->writeMergedRow((string) $indicator->formula_description, 'A', $lastCol, center: true);

        $this->writeMergedRow('RESPONSABILIDADES', 'A', $lastCol, head: true);
        $this->writeHeaderRow(['RESULTADOS Y MEDICION', 'RESULTADOS', 'MEDICION'], [5, 5, 4], $lastCol);
        $this->writeDataRow([
            'Director de Operaciones / Director(a) Financiero',
            '%',
            'No. siniestros / No. de servicios',
        ], [5, 5, 4], center: true, lastCol: $lastCol);

        $financeRows = (array) ($context['financeRows'] ?? []);
        $this->writeMergedRow('RESULTADOS', 'A', $lastCol, head: true);
        $this->writeFtOp03CriteriaHeader($monthLabels, $lastCol);
        $this->writeFtOp03MonthlyRow('TOTAL FACTURACION MENSUAL', (array) ($financeRows['facturacion'] ?? []), (float) ($financeRows['totals']['facturacion'] ?? 0), money: true);
        $this->writeFtOp03MonthlyRow('VALOR PAGADO MENSUAL', (array) ($financeRows['pagado'] ?? []), (float) ($financeRows['totals']['pagado'] ?? 0), money: true);
        $this->writeFtOp03MonthlyRow('% CUMPLIMIENTO', (array) ($financeRows['cumplimiento'] ?? []), (float) ($financeRows['totals']['cumplimiento'] ?? 0), percent: true);
        $this->writeFtOp03FixedValueRow('META', number_format((float) $indicator->target_value, 0).'%');
        $this->writeFtOp03FixedValueRow('CRITICO', number_format((float) ($indicator->critical_value ?? 0), 0).'%');

        $this->borderRange('A'.$fichaStartRow.':N'.($this->row - 1));

        $this->writeMergedRow('GRAFICOS', 'A', $lastCol, head: true, center: true);
        $this->row++;
        $this->embedChartImage((string) ($context['chartImages']['finance'] ?? ''), 'A', $this->row, 280);
        $this->row += 19;

        $incidentRows = (array) ($context['incidentRows'] ?? []);
        $this->writeMergedRow('RESULTADOS POR CANTIDAD DE CLIENTES', 'A', $lastCol, head: true);
        $this->writeFtOp03CriteriaHeader($monthLabels, $lastCol);
        $this->writeFtOp03MonthlyRow('TOTAL DE CLIENTES MENSUAL', (array) ($incidentRows['clientes'] ?? []), (float) ($incidentRows['totals']['clientes'] ?? 0));
        $this->writeFtOp03MonthlyRow('TOTAL SINIESTROS MENSUAL', (array) ($incidentRows['siniestros'] ?? []), (float) ($incidentRows['totals']['siniestros'] ?? 0));
        $this->writeFtOp03MonthlyRow('% SINIESTROS', (array) ($incidentRows['porcentaje'] ?? []), (float) ($incidentRows['totals']['porcentaje'] ?? 0), percent: true);

        $this->row++;
        $this->embedChartImage((string) ($context['chartImages']['incident'] ?? ''), 'A', $this->row, 280);
        $this->row += 19;

        $quarterlyTables = (array) ($context['quarterlyTables'] ?? []);
        $chartImages = (array) ($context['chartImages'] ?? []);
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $table = (array) ($quarterlyTables[$quarter] ?? []);
            $this->writeMergedRow($quarter.'º TRIMESTRE — CLASIFICACION SINIESTROS', 'A', $lastCol, head: true);
            $headerRow = $this->row;
            $this->sheet->setCellValue('A'.$this->row, 'TIPO DE SINIESTRO');
            $this->sheet->setCellValue('B'.$this->row, 'CANTIDAD');
            $this->sheet->setCellValue('C'.$this->row, '%');
            $this->styleTableHeader('A'.$this->row.':C'.$this->row);
            $this->row++;

            foreach ((array) ($table['rows'] ?? []) as $rowData) {
                $this->sheet->setCellValue('A'.$this->row, strtoupper((string) ($rowData['type'] ?? '')));
                $this->sheet->setCellValue('B'.$this->row, number_format((float) ($rowData['qty'] ?? 0), 0, ',', '.'));
                $this->sheet->setCellValue('C'.$this->row, number_format((float) ($rowData['pct'] ?? 0), 2).'%');
                $this->row++;
            }

            $this->sheet->setCellValue('A'.$this->row, 'TOTAL');
            $this->sheet->setCellValue('B'.$this->row, number_format((float) ($table['total_qty'] ?? 0), 0, ',', '.'));
            $this->sheet->setCellValue('C'.$this->row, '100%');
            $this->styleHeadRow('A'.$this->row.':C'.$this->row);
            $this->borderRange('A'.$headerRow.':C'.$this->row);
            $this->row++;

            $this->embedChartImage((string) ($chartImages['quarter_'.$quarter] ?? ''), 'D', $this->row - 4, 180);
            $this->row += 12;
        }

        $this->writeAnalysisTable($context);
    }

    /**
     * @param  list<string>  $monthLabels
     */
    private function writeFtOp03CriteriaHeader(array $monthLabels, string $lastCol): void
    {
        $this->sheet->setCellValue('A'.$this->row, 'CRITERIO');
        $this->styleHeadCell('A'.$this->row);
        $colIndex = 1;
        foreach ($monthLabels as $label) {
            $letter = $this->columnLetter($colIndex);
            $this->sheet->setCellValue($letter.$this->row, $label);
            $this->styleHeadCell($letter.$this->row);
            $colIndex++;
        }
        $this->sheet->setCellValue($lastCol.$this->row, 'TOTAL');
        $this->styleHeadCell($lastCol.$this->row);
        $this->borderRange('A'.$this->row.':'.$lastCol.$this->row);
        $this->row++;
    }

    /**
     * @param  array<int, float>  $values
     */
    private function writeFtOp03MonthlyRow(string $label, array $values, float $total, bool $money = false, bool $percent = false): void
    {
        $this->sheet->setCellValue('A'.$this->row, $label);
        $this->styleHeadCell('A'.$this->row);

        for ($month = 1; $month <= 12; $month++) {
            $letter = $this->columnLetter($month);
            $value = (float) ($values[$month] ?? 0);
            $formatted = $money
                ? '$ '.number_format($value, 0, ',', '.')
                : ($percent ? number_format($value, 2).'%' : number_format($value, 0, ',', '.'));
            $this->sheet->setCellValue($letter.$this->row, $formatted);
            $this->styleBodyCell($letter.$this->row, center: true);
        }

        $totalFormatted = $money
            ? '$ '.number_format($total, 0, ',', '.')
            : ($percent ? number_format($total, 2).'%' : number_format($total, 0, ',', '.'));
        $this->sheet->setCellValue('N'.$this->row, $totalFormatted);
        $this->styleBodyCell('N'.$this->row, center: true);
        $this->borderRange('A'.$this->row.':N'.$this->row);
        $this->row++;
    }

    private function writeFtOp03FixedValueRow(string $label, string $value): void
    {
        $this->sheet->setCellValue('A'.$this->row, $label);
        $this->styleHeadCell('A'.$this->row);

        for ($month = 1; $month <= 12; $month++) {
            $letter = $this->columnLetter($month);
            $this->sheet->setCellValue($letter.$this->row, $value);
            $this->styleBodyCell($letter.$this->row, center: true);
        }

        $this->sheet->setCellValue('N'.$this->row, $value);
        $this->styleBodyCell('N'.$this->row, center: true);
        $this->borderRange('A'.$this->row.':N'.$this->row);
        $this->row++;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function writeAnalysisTable(array $context): void
    {
        $sheetRows = (array) ($context['sheetRows'] ?? []);
        $selectedYear = (int) ($context['selectedYear'] ?? $context['year']);

        $headerRow = $this->row;
        $this->sheet->mergeCells('A'.$this->row.':C'.$this->row);
        $this->sheet->setCellValue('A'.$this->row, 'ANALISIS DE RESULTADOS');
        $this->sheet->setCellValue('D'.$this->row, 'CUMPLE');
        $this->sheet->setCellValue('E'.$this->row, 'MEJORA');
        $this->styleHeadCell('A'.$this->row);
        $this->styleHeadCell('D'.$this->row);
        $this->styleHeadCell('E'.$this->row);
        $this->row++;

        foreach ($sheetRows as $rowData) {
            $this->sheet->setCellValue('A'.$this->row, (string) $selectedYear);
            $this->sheet->setCellValue('B'.$this->row, (string) ($rowData['month'] ?? ''));
            $this->sheet->setCellValue('C'.$this->row, (string) ($rowData['analysis'] ?? ''));
            $this->sheet->setCellValue('D'.$this->row, ($rowData['has_capture'] ?? false) ? (($rowData['complies'] ?? false) ? 'SI' : 'NO') : '');
            $this->sheet->setCellValue('E'.$this->row, ($rowData['has_capture'] ?? false) ? (($rowData['improvement'] ?? false) ? 'SI' : 'NO') : '');
            $this->borderRange('A'.$this->row.':E'.$this->row);
            $this->row++;
        }

        $this->borderRange('A'.$headerRow.':E'.($this->row - 1));
    }

    /**
     * @param  list<array<string, mixed>>  $sheetRows
     */
    private function writeMonthHeaderRow(array $sheetRows): void
    {
        $col = 0;
        foreach ($sheetRows as $rowData) {
            $letter = $this->columnLetter($col);
            $this->sheet->setCellValue($letter.$this->row, (string) ($rowData['month'] ?? ''));
            $this->styleHeadCell($letter.$this->row, fill: 'dbeafe');
            $col++;
        }
        $this->borderRange('A'.$this->row.':L'.$this->row);
        $this->row++;
    }

    /**
     * @param  list<array<string, mixed>>  $sheetRows
     */
    private function writeMonthValues(array $sheetRows, string $key): void
    {
        $col = 0;
        foreach ($sheetRows as $rowData) {
            $letter = $this->columnLetter($col);
            $value = (float) ($rowData[$key] ?? 0);
            $this->sheet->setCellValue($letter.$this->row, rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.'));
            $this->styleBodyCell($letter.$this->row, center: true);
            $col++;
        }
        $this->borderRange('A'.$this->row.':L'.$this->row);
        $this->row++;
    }

    /**
     * @param  list<array<string, mixed>>  $sheetRows
     */
    private function writeMonthPercentages(array $sheetRows): void
    {
        $col = 0;
        foreach ($sheetRows as $rowData) {
            $letter = $this->columnLetter($col);
            $this->sheet->setCellValue($letter.$this->row, number_format((float) ($rowData['result_percentage'] ?? 0), 2).'%');
            $fill = ($rowData['complies'] ?? false) ? 'dcfce7' : 'fee2e2';
            $this->styleBodyCell($letter.$this->row, center: true, fill: $fill);
            $col++;
        }
        $this->borderRange('A'.$this->row.':L'.$this->row);
        $this->row++;
    }

    /**
     * @param  list<string>  $values
     */
    private function writeFixedMonthRow(array $values, bool $head = false): void
    {
        $col = 0;
        foreach ($values as $value) {
            $letter = $this->columnLetter($col);
            $this->sheet->setCellValue($letter.$this->row, $value);
            if ($head) {
                $this->styleHeadCell($letter.$this->row);
            } else {
                $this->styleBodyCell($letter.$this->row, center: true);
            }
            $col++;
        }
        $this->borderRange('A'.$this->row.':L'.$this->row);
        $this->row++;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<int>  $spans
     */
    private function writeHeaderRow(array $headers, array $spans, string $lastCol = 'L'): void
    {
        $colIndex = 0;
        foreach ($headers as $index => $header) {
            $span = $spans[$index] ?? 1;
            $start = $this->columnLetter($colIndex);
            $end = $this->columnLetter($colIndex + $span - 1);
            if ($span > 1) {
                $this->sheet->mergeCells($start.$this->row.':'.$end.$this->row);
            }
            $this->sheet->setCellValue($start.$this->row, $header);
            $this->styleHeadCell($start.$this->row);
            $colIndex += $span;
        }

        $this->borderRange('A'.$this->row.':'.$lastCol.$this->row);
        $this->row++;
    }

    /**
     * @param  list<string>  $values
     * @param  list<int>  $spans
     */
    private function writeDataRow(array $values, array $spans, bool $center = false, string $lastCol = 'L'): void
    {
        $colIndex = 0;
        foreach ($values as $index => $value) {
            $span = $spans[$index] ?? 1;
            $start = $this->columnLetter($colIndex);
            $end = $this->columnLetter($colIndex + $span - 1);
            if ($span > 1) {
                $this->sheet->mergeCells($start.$this->row.':'.$end.$this->row);
            }
            $this->sheet->setCellValue($start.$this->row, $value);
            $this->styleBodyCell($start.$this->row, center: $center);
            $colIndex += $span;
        }

        $this->borderRange('A'.$this->row.':'.$lastCol.$this->row);
        $this->row++;
    }

    private function writeSplitRow(string $leftHead, string $leftValue, string $rightHead, string $rightValue, string $startCol, string $leftEnd, string $rightStart, string $endCol): void
    {
        $this->sheet->mergeCells($startCol.$this->row.':'.$leftEnd.$this->row);
        $this->sheet->setCellValue($startCol.$this->row, $leftHead);
        $this->styleHeadCell($startCol.$this->row);

        $this->sheet->mergeCells($rightStart.$this->row.':'.$endCol.$this->row);
        $this->sheet->setCellValue($rightStart.$this->row, $rightHead);
        $this->styleHeadCell($rightStart.$this->row);
        $this->borderRange($startCol.$this->row.':'.$endCol.$this->row);
        $this->row++;

        $this->sheet->mergeCells($startCol.$this->row.':'.$leftEnd.$this->row);
        $this->sheet->setCellValue($startCol.$this->row, $leftValue);
        $this->styleBodyCell($startCol.$this->row);

        $this->sheet->mergeCells($rightStart.$this->row.':'.$endCol.$this->row);
        $this->sheet->setCellValue($rightStart.$this->row, $rightValue);
        $this->styleBodyCell($rightStart.$this->row, center: true);
        $this->borderRange($startCol.$this->row.':'.$endCol.$this->row);
        $this->row++;
    }

    private function writeMergedRow(string $text, string $startCol, string $endCol, bool $head = false, bool $center = false, bool $bold = false, ?string $fill = null): void
    {
        $range = $startCol.$this->row.':'.$endCol.$this->row;
        $this->sheet->mergeCells($range);
        $this->sheet->setCellValue($startCol.$this->row, $text);

        if ($head) {
            $this->styleHeadCell($startCol.$this->row, center: $center, fill: $fill ?? 'f3f4f6');
        } else {
            $this->styleBodyCell($startCol.$this->row, center: $center, fill: $fill);
        }

        if ($bold) {
            $this->sheet->getStyle($startCol.$this->row)->getFont()->setBold(true);
        }

        $this->borderRange($range);
        $this->row++;
    }

    private function embedLogo(string $coordinate): void
    {
        $logoPath = public_path('images/logoSj.png');
        if (! is_file($logoPath)) {
            return;
        }

        $drawing = new Drawing;
        $drawing->setName('Logo');
        $drawing->setPath($logoPath);
        $drawing->setHeight(48);
        $drawing->setCoordinates($coordinate);
        $drawing->setWorksheet($this->sheet);
    }

    private function embedChartImage(string $dataUri, string $column, int $row, int $height): void
    {
        if ($dataUri === '' || ! str_contains($dataUri, ',')) {
            return;
        }

        $binary = base64_decode(explode(',', $dataUri, 2)[1] ?? '', true);
        if ($binary === false || $binary === '') {
            return;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return;
        }

        $drawing = new MemoryDrawing;
        $drawing->setName('Grafico');
        $drawing->setImageResource($image);
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
        $drawing->setHeight($height);
        $drawing->setCoordinates($column.$row);
        $drawing->setWorksheet($this->sheet);
    }

    private function styleTableHeader(string $range): void
    {
        $this->sheet->getStyle($range)->getFont()->setBold(true);
        $this->sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF003366');
        $this->sheet->getStyle($range)->getFont()->getColor()->setARGB('FFFFFFFF');
        $this->sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function styleHeadRow(string $range, ?string $fill = 'f3f4f6'): void
    {
        $this->sheet->getStyle($range)->getFont()->setBold(true);
        $this->sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF'.($fill ?? 'f3f4f6'));
        $this->sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function styleHeadCell(string $cell, bool $center = true, ?string $fill = 'f3f4f6'): void
    {
        $this->sheet->getStyle($cell)->getFont()->setBold(true);
        $this->sheet->getStyle($cell)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF'.($fill ?? 'f3f4f6'));
        if ($center) {
            $this->sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $this->sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function styleBodyCell(string $cell, bool $center = false, ?string $fill = null): void
    {
        if ($fill !== null) {
            $this->sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF'.$fill);
        }
        if ($center) {
            $this->sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $this->sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function borderRange(string $range): void
    {
        $this->sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function applyColumnWidths(string $startCol, string $endCol, float $width): void
    {
        $startIndex = $this->columnIndex($startCol);
        $endIndex = $this->columnIndex($endCol);

        for ($index = $startIndex; $index <= $endIndex; $index++) {
            $this->sheet->getColumnDimension($this->columnLetter($index))->setWidth($width);
        }
    }

    private function columnIndex(string $column): int
    {
        $index = 0;
        $length = strlen($column);

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($column[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index += 1;
        while ($index > 0) {
            $index--;
            $letter = chr(ord('A') + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
