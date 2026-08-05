<?php

namespace App\Services\Indicadores;

class IndicatorPdfChartRenderer
{
    private const COLOR_BAR_DENOM = [47, 111, 217];

    private const COLOR_BAR_NUM = [120, 182, 63];

    private const COLOR_LINE_RESULT = [209, 47, 47];

    private const COLOR_LINE_META = [68, 68, 68];

    private const COLOR_BRAND_BLUE = [0, 51, 102];

    private const COLOR_BRAND_NAVY = [0, 82, 155];

    private const COLOR_PURPLE = [147, 51, 234];

    private const PIE_COLORS = [
        [47, 111, 217],
        [120, 182, 63],
        [209, 47, 47],
        [245, 158, 11],
        [147, 51, 234],
        [14, 165, 233],
    ];

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string|null>
     */
    public function buildChartImages(array $context): array
    {
        if (! extension_loaded('gd')) {
            return [];
        }

        $indicatorCode = (string) ($context['indicator']->code ?? '');

        $dimensions = $this->chartDimensions($context);

        if ($indicatorCode === 'FT-OP-03') {
            return $this->buildFtOp03ChartImages($context, $dimensions);
        }

        $payload = $context['chartPayload'] ?? [];

        if ($payload === []) {
            return [];
        }

        return [
            'main' => $this->mixedBarLineDataUri(
                categories: (array) ($payload['months'] ?? []),
                barSeries: [
                    ['label' => (string) ($payload['denominator_label'] ?? 'Denominador'), 'data' => (array) ($payload['denominator'] ?? []), 'color' => self::COLOR_BAR_DENOM],
                    ['label' => (string) ($payload['numerator_label'] ?? 'Numerador'), 'data' => (array) ($payload['numerator'] ?? []), 'color' => self::COLOR_BAR_NUM],
                ],
                lineSeries: [
                    ['label' => '% Cumplimiento', 'data' => (array) ($payload['result_percentage'] ?? []), 'color' => self::COLOR_LINE_RESULT, 'dashed' => false],
                    ['label' => 'Meta', 'data' => (array) ($payload['meta'] ?? []), 'color' => self::COLOR_LINE_META, 'dashed' => true],
                ],
                title: ($context['pdfOmitChartTitle'] ?? false) ? '' : (string) ($payload['title'] ?? ''),
                rightAxisMax: 100,
                width: $dimensions['width'],
                height: $dimensions['height'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{width: int, height: int, pieWidth: int, pieHeight: int}
     */
    private function chartDimensions(array $context): array
    {
        return [
            'width' => (int) ($context['pdfChartWidth'] ?? 860),
            'height' => (int) ($context['pdfChartHeight'] ?? 380),
            'pieWidth' => (int) ($context['pdfPieWidth'] ?? 420),
            'pieHeight' => (int) ($context['pdfPieHeight'] ?? 320),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string|null>
     */
    /**
     * @param  array{width: int, height: int, pieWidth: int, pieHeight: int}  $dimensions
     * @return array<string, string|null>
     */
    private function buildFtOp03ChartImages(array $context, array $dimensions): array
    {
        $images = [];

        $financePayload = (array) ($context['financeChartPayload'] ?? []);
        if ($financePayload !== []) {
            $images['finance'] = $this->comboChartDataUri(
                payload: $financePayload,
                bar1Key: 'facturacion',
                bar2Key: 'pagado',
                lineKeys: ['cumplimiento', 'meta', 'critico'],
                bar1Label: 'Facturacion',
                bar2Label: 'Pagado',
                lineLabels: ['% Cumplimiento', 'Meta', 'Critico'],
                width: $dimensions['width'],
                height: $dimensions['height'],
            );
        }

        $incidentPayload = (array) ($context['incidentChartPayload'] ?? []);
        if ($incidentPayload !== []) {
            $images['incident'] = $this->comboChartDataUri(
                payload: $incidentPayload,
                bar1Key: 'clientes',
                bar2Key: 'siniestros',
                lineKeys: ['porcentaje', 'meta', 'critico'],
                bar1Label: 'Clientes',
                bar2Label: 'Siniestros',
                lineLabels: ['% Siniestros', 'Meta', 'Critico'],
                width: $dimensions['width'],
                height: $dimensions['height'],
            );
        }

        $quarterPayload = (array) ($context['quarterChartPayload'] ?? []);
        foreach ($quarterPayload as $quarter => $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $images['quarter_'.$quarter] = $this->pieChartDataUri(
                $payload,
                width: $dimensions['pieWidth'],
                height: $dimensions['pieHeight'],
            );
        }

        return $images;
    }

    /**
     * @param  list<string>  $categories
     * @param  list<array{label: string, data: list<float|int|null>, color: list{int, int, int}}>  $barSeries
     * @param  list<array{label: string, data: list<float|int|null>, color: list{int, int, int}, dashed?: bool}>  $lineSeries
     */
    public function mixedBarLineDataUri(
        array $categories,
        array $barSeries,
        array $lineSeries,
        string $title = '',
        int $rightAxisMax = 100,
        int $width = 860,
        int $height = 380,
    ): ?string {
        if ($categories === []) {
            return null;
        }

        $image = $this->createCanvas($width, $height);
        if ($image === null) {
            return null;
        }

        $legend = array_merge(
            array_map(fn (array $series): string => $series['label'], $barSeries),
            array_map(fn (array $series): string => $series['label'], $lineSeries),
        );
        $legendColors = array_merge(
            array_map(fn (array $series): array => $series['color'], $barSeries),
            array_map(fn (array $series): array => $series['color'], $lineSeries),
        );

        $legendHeight = $this->legendHeight($legend, $width - 24);
        $categoryBand = 22;
        $topMargin = $title !== '' ? 28 : 12;

        $plot = [
            'left' => 55,
            'top' => $topMargin,
            'right' => $width - 55,
            'bottom' => $height - $categoryBand - $legendHeight - 8,
        ];
        $count = count($categories);

        $barValues = [];
        foreach ($barSeries as $series) {
            $barValues = array_merge($barValues, $series['data']);
        }
        $leftMax = max($this->maxNumericValues($barValues), 1.0);

        if ($title !== '') {
            $this->drawTitle($image, $title, $width);
        }

        $this->drawGrid($image, $plot, $count);
        $this->drawCategoryLabels($image, $categories, $plot, $count);

        $slotWidth = ($plot['right'] - $plot['left']) / max($count, 1);
        $barCount = count($barSeries);
        $barWidth = min(18, ($slotWidth * 0.7) / max($barCount, 1));

        foreach ($barSeries as $barIndex => $series) {
            foreach ($series['data'] as $index => $value) {
                $numeric = (float) ($value ?? 0);
                $xCenter = $plot['left'] + ($index * $slotWidth) + ($slotWidth / 2);
                $offset = ($barIndex - (($barCount - 1) / 2)) * ($barWidth + 2);
                $barHeight = ($numeric / $leftMax) * ($plot['bottom'] - $plot['top']);
                $x1 = (int) round($xCenter + $offset - ($barWidth / 2));
                $y1 = (int) round($plot['bottom'] - $barHeight);
                $x2 = (int) round($xCenter + $offset + ($barWidth / 2));
                $this->filledRectangle($image, $x1, $y1, $x2, $plot['bottom'], $series['color']);
            }
        }

        foreach ($lineSeries as $series) {
            $points = [];
            foreach ($series['data'] as $index => $value) {
                $numeric = (float) ($value ?? 0);
                $x = $plot['left'] + ($index * $slotWidth) + ($slotWidth / 2);
                $y = $plot['bottom'] - (($numeric / max($rightAxisMax, 1)) * ($plot['bottom'] - $plot['top']));
                $points[] = [(int) round($x), (int) round($y)];
            }

            $this->drawPolyline($image, $points, $series['color'], (bool) ($series['dashed'] ?? false));
        }

        $this->drawLegend($image, $legend, $legendColors, $width - 24, (int) ($plot['bottom'] + $categoryBand + 4));

        return $this->encodePng($image);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $lineKeys
     * @param  list<string>  $lineLabels
     */
    public function comboChartDataUri(
        array $payload,
        string $bar1Key,
        string $bar2Key,
        array $lineKeys,
        string $bar1Label,
        string $bar2Label,
        array $lineLabels,
        int $width = 860,
        int $height = 360,
    ): ?string {
        $categories = (array) ($payload['months'] ?? []);
        if ($categories === []) {
            return null;
        }

        $lineColors = [self::COLOR_LINE_RESULT, self::COLOR_LINE_META, self::COLOR_PURPLE];
        $lineSeries = [];
        foreach ($lineKeys as $index => $key) {
            $lineSeries[] = [
                'label' => $lineLabels[$index] ?? $key,
                'data' => (array) ($payload[$key] ?? []),
                'color' => $lineColors[$index] ?? self::COLOR_LINE_META,
                'dashed' => $index > 0,
            ];
        }

        return $this->mixedBarLineDataUri(
            categories: $categories,
            barSeries: [
                ['label' => $bar1Label, 'data' => (array) ($payload[$bar1Key] ?? []), 'color' => self::COLOR_BRAND_BLUE],
                ['label' => $bar2Label, 'data' => (array) ($payload[$bar2Key] ?? []), 'color' => self::COLOR_BRAND_NAVY],
            ],
            lineSeries: $lineSeries,
            title: '',
            rightAxisMax: 100,
            width: $width,
            height: $height,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function pieChartDataUri(array $payload, int $width = 420, int $height = 320): ?string
    {
        $items = (array) ($payload['data'] ?? []);
        $values = array_map(fn (array $item): float => (float) ($item['value'] ?? 0), $items);
        $total = array_sum($values);

        $image = $this->createCanvas($width, $height);
        if ($image === null) {
            return null;
        }

        $title = (string) ($payload['title'] ?? '');
        $this->drawTitle($image, $title, $width);

        $centerX = (int) ($width / 2);
        $centerY = (int) (($height / 2) + 12);
        $radius = min($width, $height) / 3;

        if ($total <= 0) {
            $this->drawCenteredText($image, 'Sin datos', $centerX, $centerY);

            return $this->encodePng($image);
        }

        $startAngle = 0.0;
        foreach ($values as $index => $value) {
            if ($value <= 0) {
                continue;
            }

            $sliceAngle = ($value / $total) * 360;
            $color = self::PIE_COLORS[$index % count(self::PIE_COLORS)];
            $this->drawPieSlice($image, $centerX, $centerY, (int) $radius, $startAngle, $startAngle + $sliceAngle, $color);
            $startAngle += $sliceAngle;
        }

        $labels = array_map(fn (array $item): string => (string) ($item['name'] ?? ''), $items);
        $this->drawLegend($image, $labels, self::PIE_COLORS, $width, $height - 28);

        return $this->encodePng($image);
    }

    private function createCanvas(int $width, int $height): ?\GdImage
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            return null;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width, $height, $white);

        return $image;
    }

    /**
     * @param  list{int, int, int}  $color
     */
    private function allocateColor(\GdImage $image, array $color): int
    {
        return imagecolorallocate($image, $color[0], $color[1], $color[2]);
    }

    /**
     * @param  list{int, int, int}  $color
     */
    private function filledRectangle(\GdImage $image, int $x1, int $y1, int $x2, int $y2, array $color): void
    {
        imagefilledrectangle($image, $x1, $y1, $x2, $y2, $this->allocateColor($image, $color));
    }

    /**
     * @param  list{int, int, int}  $color
     */
    private function drawPolyline(\GdImage $image, array $points, array $color, bool $dashed): void
    {
        if (count($points) < 2) {
            return;
        }

        $allocated = $this->allocateColor($image, $color);

        for ($index = 1; $index < count($points); $index++) {
            [$x1, $y1] = $points[$index - 1];
            [$x2, $y2] = $points[$index];

            if ($dashed) {
                $this->drawDashedLine($image, $x1, $y1, $x2, $y2, $allocated);

                continue;
            }

            imageline($image, $x1, $y1, $x2, $y2, $allocated);
            imagefilledellipse($image, $x2, $y2, 6, 6, $allocated);
        }
    }

    private function drawDashedLine(\GdImage $image, int $x1, int $y1, int $x2, int $y2, int $color): void
    {
        $length = hypot($x2 - $x1, $y2 - $y1);
        if ($length <= 0) {
            return;
        }

        $dash = 6;
        $gap = 4;
        $steps = (int) floor($length / ($dash + $gap));

        for ($step = 0; $step <= $steps; $step++) {
            $start = ($step * ($dash + $gap)) / $length;
            $end = min((($step * ($dash + $gap)) + $dash) / $length, 1);
            imageline(
                $image,
                (int) round($x1 + (($x2 - $x1) * $start)),
                (int) round($y1 + (($y2 - $y1) * $start)),
                (int) round($x1 + (($x2 - $x1) * $end)),
                (int) round($y1 + (($y2 - $y1) * $end)),
                $color,
            );
        }
    }

    /**
     * @param  list{int, int, int}  $color
     */
    private function drawPieSlice(\GdImage $image, int $centerX, int $centerY, int $radius, float $start, float $end, array $color): void
    {
        imagefilledarc(
            $image,
            $centerX,
            $centerY,
            $radius * 2,
            $radius * 2,
            $start,
            $end,
            $this->allocateColor($image, $color),
            IMG_ARC_PIE,
        );
    }

    /**
     * @param  array{left: int, top: int, right: int, bottom: int}  $plot
     */
    private function drawGrid(\GdImage $image, array $plot, int $count): void
    {
        $gridColor = imagecolorallocate($image, 226, 232, 240);
        imagerectangle($image, $plot['left'], $plot['top'], $plot['right'], $plot['bottom'], $gridColor);

        for ($index = 1; $index < 4; $index++) {
            $y = (int) round($plot['top'] + (($plot['bottom'] - $plot['top']) * ($index / 4)));
            imageline($image, $plot['left'], $y, $plot['right'], $y, $gridColor);
        }

        if ($count > 1) {
            $slotWidth = ($plot['right'] - $plot['left']) / $count;
            for ($index = 1; $index < $count; $index++) {
                $x = (int) round($plot['left'] + ($index * $slotWidth));
                imageline($image, $x, $plot['top'], $x, $plot['bottom'], $gridColor);
            }
        }
    }

    /**
     * @param  list<string>  $categories
     * @param  array{left: int, top: int, right: int, bottom: int}  $plot
     */
    private function drawCategoryLabels(\GdImage $image, array $categories, array $plot, int $count): void
    {
        $textColor = imagecolorallocate($image, 30, 41, 59);
        $slotWidth = ($plot['right'] - $plot['left']) / max($count, 1);

        foreach ($categories as $index => $label) {
            $x = (int) round($plot['left'] + ($index * $slotWidth) + ($slotWidth / 2) - (strlen((string) $label) * 3));
            imagestring($image, 2, max($plot['left'], $x), $plot['bottom'] + 8, (string) $label, $textColor);
        }
    }

    private function drawTitle(\GdImage $image, string $title, int $width): void
    {
        if ($title === '') {
            return;
        }

        $textColor = imagecolorallocate($image, 0, 51, 102);
        $x = max(10, (int) (($width / 2) - (strlen($title) * 3)));
        imagestring($image, 4, $x, 8, $title, $textColor);
    }

    private function drawCenteredText(\GdImage $image, string $text, int $centerX, int $centerY): void
    {
        $textColor = imagecolorallocate($image, 100, 116, 139);
        imagestring($image, 3, $centerX - (strlen($text) * 3), $centerY - 6, $text, $textColor);
    }

    /**
     * @param  list<string>  $labels
     */
    private function legendHeight(array $labels, int $maxWidth): int
    {
        $x = 0;
        $rows = 1;
        $rowHeight = 15;

        foreach ($labels as $label) {
            if ($label === '') {
                continue;
            }

            $itemWidth = 24 + (strlen($label) * 6);

            if ($x > 0 && ($x + $itemWidth) > $maxWidth) {
                $x = 0;
                $rows++;
            }

            $x += $itemWidth + 10;
        }

        return ($rows * $rowHeight) + 4;
    }

    /**
     * @param  list<string>  $labels
     * @param  list<array{int, int, int}>  $colors
     */
    private function drawLegend(\GdImage $image, array $labels, array $colors, int $maxWidth, int $startY): void
    {
        $x = 12;
        $y = $startY;
        $rowHeight = 15;
        $textColor = imagecolorallocate($image, 30, 41, 59);

        foreach ($labels as $index => $label) {
            if ($label === '') {
                continue;
            }

            $color = $colors[$index] ?? self::COLOR_LINE_META;
            $itemWidth = 24 + (strlen($label) * 6);

            if ($x > 12 && ($x + $itemWidth) > (12 + $maxWidth)) {
                $x = 12;
                $y += $rowHeight;
            }

            $this->filledRectangle($image, $x, $y, $x + 10, $y + 10, $color);
            imagestring($image, 2, $x + 14, $y - 1, $label, $textColor);
            $x += $itemWidth + 10;
        }
    }

    /**
     * @param  list<float|int|null>  $values
     */
    private function maxNumericValues(array $values): float
    {
        $max = 0.0;
        foreach ($values as $value) {
            $max = max($max, (float) ($value ?? 0));
        }

        return $max;
    }

    private function encodePng(\GdImage $image): string
    {
        ob_start();
        imagepng($image);
        $binary = ob_get_clean() ?: '';
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}
