<?php

namespace App\Services\Indicadores\ManagementReport;

class ManagementReportChartUpdater
{
    private const MONTH_LABELS = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

    /**
     * @param  array{numerators: array<int, float|int>, denominators: array<int, float|int>, percentages: array<int, float|int|null>, meta: float|int}  $series
     */
    public function apply(string $chartXml, array $series): string
    {
        $numerators = $this->padSeries($series['numerators'] ?? []);
        $denominators = $this->padSeries($series['denominators'] ?? []);
        $percentages = $this->padSeries(
            array_map(
                function ($value) use ($series) {
                    if ($value === null) {
                        return 0;
                    }

                    if (($series['percentage_scale'] ?? 'percent') === 'count') {
                        return round((float) $value, 6);
                    }

                    return round(((float) $value) / 100, 6);
                },
                $series['percentages'] ?? []
            )
        );
        $metaScale = ($series['percentage_scale'] ?? 'percent') === 'count' ? 1.0 : 100.0;
        $metaDecimal = round(((float) ($series['meta'] ?? 0)) / $metaScale, 6);
        $metaLine = array_fill(0, 12, $metaDecimal);

        $seriesValues = [$numerators, $denominators, $percentages, $metaLine];
        $seriesIndex = 0;

        $updated = preg_replace_callback(
            '/<c:ser>.*?<\/c:ser>/s',
            function (array $match) use (&$seriesIndex, $seriesValues): string {
                $block = $match[0];
                $block = $this->replaceCacheInBlock($block, 'strCache', self::MONTH_LABELS, fn ($value) => (string) $value);

                if (isset($seriesValues[$seriesIndex])) {
                    $block = $this->replaceCacheInBlock(
                        $block,
                        'numCache',
                        $seriesValues[$seriesIndex],
                        fn ($value) => $this->formatNumber($value)
                    );
                }

                $seriesIndex++;

                return $block;
            },
            $chartXml
        );

        return is_string($updated) ? $updated : $chartXml;
    }

    /**
     * @param  array<int, float|int|string>  $values
     */
    private function replaceCacheInBlock(string $block, string $cacheTag, array $values, callable $formatter): string
    {
        return preg_replace_callback(
            '/<c:'.$cacheTag.'>.*?<\/c:'.$cacheTag.'>/s',
            function (array $match) use ($values, $formatter): string {
                $cache = $match[0];

                $cache = preg_replace_callback(
                    '/<c:pt idx="(\d+)"><c:v>[^<]*<\/c:v><\/c:pt>/',
                    function (array $point) use ($values, $formatter): string {
                        $idx = (int) $point[1];
                        $value = $values[$idx] ?? 0;

                        return '<c:pt idx="'.$idx.'"><c:v>'.$formatter($value).'</c:v></c:pt>';
                    },
                    $cache
                ) ?? $cache;

                return preg_replace('/<c:ptCount val="\d+"\/>/', '<c:ptCount val="12"/>', $cache) ?? $cache;
            },
            $block
        ) ?? $block;
    }

    /**
     * @param  array<int, float|int>  $values
     * @return array<int, float|int>
     */
    private function padSeries(array $values): array
    {
        $padded = array_values($values);

        for ($index = count($padded); $index < 12; $index++) {
            $padded[$index] = 0;
        }

        return array_slice($padded, 0, 12);
    }

    private function formatNumber(float|int $value): string
    {
        if (is_int($value) || floor((float) $value) == (float) $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
    }
}
