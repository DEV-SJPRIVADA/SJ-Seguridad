<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;

class PlaneacionPeriodService
{
    /**
     * @var array<int, string>
     */
    public const MONTH_LABELS = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    public function monthLabel(int $month): string
    {
        return self::MONTH_LABELS[$month] ?? (string) $month;
    }

    /**
     * Versión que aplica a un mes de captura.
     *
     * v1 rige desde su mes de publicación inclusive. Al publicar vN (N>1) en el
     * mes P, P queda cerrado; los meses anteriores siguen en la versión previa
     * y los posteriores usan vN.
     */
    public function versionForCapture(QualityPlanningIndicator $indicator, int $year, int $month): PlaneacionCapturePeriod
    {
        $indicator->loadMissing('versions');
        $targetYm = $this->yearMonthValue($year, $month);
        $periodLabel = $this->monthLabel($month).' '.$year;

        $published = $indicator->versions
            ->filter(fn (QualityPlanningIndicatorVersion $version): bool => $version->published_at !== null
                && in_array($version->status, [
                    QualityPlanningIndicatorVersion::STATUS_PUBLISHED,
                    QualityPlanningIndicatorVersion::STATUS_SUPERSEDED,
                ], true))
            ->sortBy('version_number')
            ->values();

        if ($published->isEmpty()) {
            return new PlaneacionCapturePeriod(
                version: null,
                closed: false,
                message: 'No hay una versión publicada para '.$periodLabel.'.',
            );
        }

        foreach ($published as $version) {
            if ((int) $version->version_number <= 1) {
                continue;
            }

            $publishedAt = $version->published_at;
            if ($publishedAt === null) {
                continue;
            }

            $publishYm = $this->yearMonthValue((int) $publishedAt->year, (int) $publishedAt->month);
            if ($publishYm === $targetYm) {
                return new PlaneacionCapturePeriod(
                    version: null,
                    closed: true,
                    message: 'El periodo de '.$periodLabel.' está cerrado porque se publicó una nueva versión ese mes.',
                );
            }
        }

        $applicable = null;

        foreach ($published as $version) {
            $publishedAt = $version->published_at;
            if ($publishedAt === null) {
                continue;
            }

            $publishYm = $this->yearMonthValue((int) $publishedAt->year, (int) $publishedAt->month);

            if ((int) $version->version_number === 1) {
                if ($targetYm >= $publishYm) {
                    $applicable = $version;
                }

                continue;
            }

            if ($targetYm > $publishYm) {
                $applicable = $version;
            }
        }

        if (! $applicable instanceof QualityPlanningIndicatorVersion) {
            return new PlaneacionCapturePeriod(
                version: null,
                closed: false,
                message: 'No hay una versión publicada para '.$periodLabel.'.',
            );
        }

        return new PlaneacionCapturePeriod(
            version: $applicable,
            closed: false,
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function yearOptions(?int $centerYear = null): array
    {
        $nowYear = (int) now()->year;
        $centerYear ??= $nowYear;
        $years = range($nowYear - 1, $nowYear + 1);
        $years[] = $centerYear;
        $years = array_values(array_unique($years));
        sort($years);

        return array_map(
            fn (int $year): array => ['value' => (string) $year, 'label' => (string) $year],
            $years,
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function monthOptions(): array
    {
        $options = [];

        foreach (self::MONTH_LABELS as $number => $label) {
            $options[] = [
                'value' => (string) $number,
                'label' => mb_convert_case($label, MB_CASE_TITLE, 'UTF-8'),
            ];
        }

        return $options;
    }

    private function yearMonthValue(int $year, int $month): int
    {
        return ($year * 12) + $month;
    }
}
