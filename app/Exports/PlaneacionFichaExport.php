<?php

namespace App\Exports;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ficha anual de Planeación (12 meses) en Excel. Formato de hoja, no un listado plano.
 */
class PlaneacionFichaExport
{
    private Worksheet $sheet;

    private int $row = 1;

    /**
     * @param  array<string, mixed>  $ficha
     */
    public function download(QualityPlanningIndicator $indicator, array $ficha, string $fileName, ?string $chartImage = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle(substr((string) $indicator->code, 0, 31));
        $this->row = 1;

        $this->writeFicha($indicator, $ficha, $chartImage);
        $this->writeAnalysis($ficha);
        $this->applyColumnWidths();

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $ficha
     */
    private function writeFicha(QualityPlanningIndicator $indicator, array $ficha, ?string $chartImage): void
    {
        $start = $this->row;
        $unitSuffix = (string) ($ficha['unit_suffix'] ?? '');
        $months = is_array($ficha['months'] ?? null) ? $ficha['months'] : [];
        $fields = is_array($ficha['fields'] ?? null) ? $ficha['fields'] : [];

        $this->sheet->mergeCells('A'.$this->row.':B'.($this->row + 3));
        $this->embedLogo('A'.$this->row);
        $this->sheet->mergeCells('C'.$this->row.':I'.($this->row + 3));
        $this->sheet->setCellValue('C'.$this->row, 'FICHA DEL INDICADOR DE GESTIÓN');
        $this->styleRange('C'.$this->row, head: false, center: true, bold: true, size: 14);

        $meta = [
            (string) $indicator->code,
            (string) ($ficha['period_label'] ?? ''),
            'Versión '.str_pad((string) ($ficha['version_number'] ?? 1), 2, '0', STR_PAD_LEFT),
            'Página 1 de 1',
        ];
        foreach ($meta as $offset => $value) {
            $this->sheet->mergeCells('J'.($this->row + $offset).':L'.($this->row + $offset));
            $this->sheet->setCellValue('J'.($this->row + $offset), $value);
            $this->styleRange('J'.($this->row + $offset), center: true);
        }
        $this->row += 4;

        $this->merged('NOMBRE DEL INDICADOR', head: true);
        $this->merged((string) ($ficha['name'] ?? $indicator->code));
        $this->split('OBJETIVO', (string) ($ficha['objective'] ?? ''), 'PROCESO', (string) ($ficha['process'] ?? '—'));

        $this->headerSpans(['UNIDAD MEDIDA', 'META', 'FRECUENCIA DE MEDICIÓN', 'TENDENCIA', 'INSUMOS PARA LA MEDICIÓN'], [2, 1, 3, 2, 4]);
        $target = ($ficha['target_value'] ?? null) !== null
            ? (string) ($ficha['target_operator'] ?? '>=').' '.number_format((float) $ficha['target_value'], 0).$unitSuffix
            : '—';
        $this->dataSpans([
            (string) ($ficha['unit_label'] ?? 'Porcentaje'),
            $target,
            (string) ($ficha['frequency'] ?? 'Mensual'),
            (string) ($ficha['tendency'] ?? ''),
            (string) ($ficha['insumos'] ?? '—'),
        ], [2, 1, 3, 2, 4]);

        $critical = ($ficha['critical_value'] ?? null) !== null
            ? number_format((float) $ficha['critical_value'], 0).$unitSuffix
            : '—';
        $this->headerSpans(['CRÍTICO', $critical], [2, 10], secondIsBody: true);

        $this->merged('FÓRMULA', head: true);
        $formula = (string) ($ficha['formula_display'] ?? '');
        $this->merged($formula !== '' ? '('.$formula.')' : '(N.A.)');

        $responsibilities = is_array($ficha['responsibilities'] ?? null) ? $ficha['responsibilities'] : [];
        $this->merged('RESPONSABILIDADES', head: true);
        $this->headerSpans(['RESULTADOS Y MEDICIÓN', 'RESULTADOS', 'MEDICIÓN'], [4, 4, 4]);
        $this->dataSpans([
            (string) ($responsibilities['measurement'] ?? '—'),
            (string) ($responsibilities['results'] ?? '—'),
            (string) ($responsibilities['measure'] ?? '—'),
        ], [4, 4, 4]);

        $this->merged('RESULTADOS', head: true);
        $this->monthRow(array_map(fn ($row): string => (string) ($row['abbrev'] ?? ''), $months), head: true);
        $this->merged((string) ($ficha['year'] ?? ''), head: true);

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $key = (string) ($field['key'] ?? '');
            $this->merged((string) ($field['label'] ?? $key), head: true);
            $values = [];
            foreach ($months as $month) {
                $raw = is_array($month) ? ($month['field_values'][$key] ?? null) : null;
                $values[] = $this->formatNumber($raw !== null ? (float) $raw : null);
            }
            $this->monthRow($values);
        }

        $this->merged('NIVEL DE CUMPLIMIENTO '.mb_strtoupper((string) ($ficha['name'] ?? ''), 'UTF-8'), head: true);
        $results = [];
        $targets = [];
        $criticals = [];
        foreach ($months as $month) {
            $hasData = (bool) ($month['has_data'] ?? false);
            $result = $month['result'] ?? null;
            $results[] = ($hasData && $result !== null) ? number_format((float) $result, 2).$unitSuffix : '';
            $targets[] = (string) ($month['target_display'] ?? '');
            $criticals[] = (string) ($month['critical_display'] ?? '');
        }
        $this->monthRow($results);
        $this->monthRow($targets);
        $this->monthRow($criticals);

        $this->borderRange('A'.$start.':L'.($this->row - 1));
        $this->merged('GRÁFICOS', head: true, navy: true);
        $this->writeChartImage($chartImage);
    }

    /**
     * @param  array<string, mixed>  $ficha
     */
    private function writeAnalysis(array $ficha): void
    {
        $headerRow = $this->row;
        $this->sheet->mergeCells('A'.$this->row.':J'.$this->row);
        $this->sheet->setCellValue('A'.$this->row, 'ANÁLISIS DE RESULTADOS');
        $this->sheet->setCellValue('K'.$this->row, 'CUMPLE');
        $this->sheet->setCellValue('L'.$this->row, 'MEJORA');
        $this->styleRange('A'.$this->row.':L'.$this->row, head: true, center: true, navy: true);
        $this->row++;

        $year = (string) ($ficha['year'] ?? '');
        $months = is_array($ficha['months'] ?? null) ? $ficha['months'] : [];
        foreach ($months as $month) {
            $hasData = (bool) ($month['has_data'] ?? false);
            $this->sheet->setCellValue('A'.$this->row, $year);
            $this->sheet->setCellValue('B'.$this->row, (string) ($month['abbrev'] ?? ''));
            $this->sheet->mergeCells('C'.$this->row.':J'.$this->row);
            $this->sheet->setCellValue('C'.$this->row, $hasData ? (string) ($month['analysis'] ?? '') : '');
            $this->sheet->setCellValue('K'.$this->row, $hasData ? (($month['complies'] ?? false) ? 'Sí' : 'No') : '');
            $this->sheet->setCellValue('L'.$this->row, $hasData ? (($month['improvement'] ?? false) ? 'Sí' : 'No') : '');
            $this->styleRange('A'.$this->row.':B'.$this->row, head: true, center: true);
            $this->styleRange('C'.$this->row.':J'.$this->row);
            $this->styleRange('K'.$this->row.':L'.$this->row, center: true);
            $this->row++;
        }

        $this->borderRange('A'.$headerRow.':L'.($this->row - 1));
    }

    private function merged(string $value, bool $head = false, bool $navy = false): void
    {
        $this->sheet->mergeCells('A'.$this->row.':L'.$this->row);
        $this->sheet->setCellValue('A'.$this->row, $value);
        $this->styleRange('A'.$this->row, head: $head, center: true, navy: $navy);
        $this->row++;
    }

    private function split(string $leftHead, string $leftValue, string $rightHead, string $rightValue): void
    {
        $this->sheet->mergeCells('A'.$this->row.':H'.$this->row);
        $this->sheet->setCellValue('A'.$this->row, $leftHead);
        $this->sheet->mergeCells('I'.$this->row.':L'.$this->row);
        $this->sheet->setCellValue('I'.$this->row, $rightHead);
        $this->styleRange('A'.$this->row.':L'.$this->row, head: true, center: true);
        $this->row++;

        $this->sheet->mergeCells('A'.$this->row.':H'.$this->row);
        $this->sheet->setCellValue('A'.$this->row, $leftValue);
        $this->sheet->mergeCells('I'.$this->row.':L'.$this->row);
        $this->sheet->setCellValue('I'.$this->row, $rightValue);
        $this->styleRange('A'.$this->row.':L'.$this->row, center: true);
        $this->row++;
    }

    /**
     * @param  list<string>  $labels
     * @param  list<int>  $spans
     */
    private function headerSpans(array $labels, array $spans, bool $secondIsBody = false): void
    {
        $this->writeSpans($labels, $spans, head: true, secondIsBody: $secondIsBody);
    }

    /**
     * @param  list<string>  $values
     * @param  list<int>  $spans
     */
    private function dataSpans(array $values, array $spans): void
    {
        $this->writeSpans($values, $spans, head: false);
    }

    /**
     * @param  list<string>  $values
     * @param  list<int>  $spans
     */
    private function writeSpans(array $values, array $spans, bool $head, bool $secondIsBody = false): void
    {
        $col = 0;
        foreach ($values as $index => $value) {
            $span = $spans[$index] ?? 1;
            $start = $this->letter($col);
            $end = $this->letter($col + $span - 1);
            if ($span > 1) {
                $this->sheet->mergeCells($start.$this->row.':'.$end.$this->row);
            }
            $this->sheet->setCellValue($start.$this->row, $value);
            $isHead = $head && ! ($secondIsBody && $index === 1);
            $this->styleRange($start.$this->row, head: $isHead, center: true);
            $col += $span;
        }
        $this->row++;
    }

    /**
     * @param  list<string>  $values
     */
    private function monthRow(array $values, bool $head = false): void
    {
        foreach (array_values($values) as $index => $value) {
            $letter = $this->letter($index);
            $this->sheet->setCellValue($letter.$this->row, $value);
            $this->styleRange($letter.$this->row, head: $head, center: true);
        }
        $this->row++;
    }

    private function styleRange(string $range, bool $head = false, bool $center = false, bool $bold = false, bool $navy = false, int $size = 10): void
    {
        $style = $this->sheet->getStyle($range);
        $font = $style->getFont()->setSize($size);
        if ($head || $bold) {
            $font->setBold(true);
        }
        if ($head || $navy) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($navy ? 'FF1E2743' : 'FFF3F4F6');
            if ($navy) {
                $font->getColor()->setARGB('FFFFFFFF');
            }
        }
        $align = $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        if ($center) {
            $align->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function borderRange(string $range): void
    {
        $this->sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function applyColumnWidths(): void
    {
        foreach (range(0, 11) as $index) {
            $this->sheet->getColumnDimension($this->letter($index))->setWidth(14);
        }
    }

    private function writeChartImage(?string $dataUri): void
    {
        $start = $this->row;
        $rows = 12;
        for ($offset = 0; $offset < $rows; $offset++) {
            $this->sheet->getRowDimension($this->row + $offset)->setRowHeight(16);
        }
        $this->sheet->mergeCells('A'.$start.':L'.($start + $rows - 1));

        if ($dataUri === null || $dataUri === '') {
            $this->sheet->setCellValue('A'.$start, 'Gráfico no disponible.');
            $this->styleRange('A'.$start, center: true);
            $this->row += $rows;

            return;
        }

        $this->embedChartImage($dataUri, 'A', $start, 190);
        $this->row += $rows;
    }

    private function embedChartImage(string $dataUri, string $column, int $row, int $height): void
    {
        if (! str_contains($dataUri, ',')) {
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
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(6);
        $drawing->setWorksheet($this->sheet);
    }

    private function embedLogo(string $coordinate): void
    {
        $path = public_path('images/logo_web.png');
        if (! is_file($path)) {
            $path = public_path('images/logoSj.png');
        }
        if (! is_file($path)) {
            return;
        }

        $drawing = new Drawing;
        $drawing->setPath($path);
        $drawing->setCoordinates($coordinate);
        $drawing->setHeight(52);
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(6);
        $drawing->setWorksheet($this->sheet);
    }

    private function formatNumber(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function letter(int $index): string
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
