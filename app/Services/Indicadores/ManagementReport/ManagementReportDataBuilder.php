<?php

namespace App\Services\Indicadores\ManagementReport;

use App\Models\Indicator;
use App\Services\Indicadores\Dashboard\OperationsDashboardService;
use App\Services\Indicadores\IndicatorConsolidadoService;
use Illuminate\Support\Collection;

class ManagementReportDataBuilder
{
    public function __construct(
        private readonly OperationsDashboardService $dashboardService,
        private readonly IndicatorConsolidadoService $consolidadoService,
        private readonly ManagementReportNarrativeBuilder $narrativeBuilder,
    ) {
    }

    public function build(int $year, int $month): array
    {
        $months = config('indicators.months', []);
        $monthName = $months[$month] ?? (string) $month;
        $dashboard = $this->dashboardService->build($year, $month);
        $previousMonth = $month === 1 ? 12 : $month - 1;
        $previousYear = $month === 1 ? $year - 1 : $year;
        $previousDashboard = $this->dashboardService->build($previousYear, $previousMonth);

        $indicators = [];
        foreach (config('indicators.management_report.indicators', []) as $definition) {
            $code = $definition['code'];
            $indicator = Indicator::query()->where('code', $code)->where('is_active', true)->firstOrFail();
            $kpi = collect($dashboard['kpis'])->firstWhere('indicator.code', $code);
            $previousKpi = collect($previousDashboard['kpis'])->firstWhere('indicator.code', $code);
            $monthly = $this->consolidadoService->getMonthlyData($indicator, $year, $month);
            $metaLabel = $kpi['meta'] ?? $this->metaLabel($indicator);

            $indicators[$code] = [
                'code' => $code,
                'slide' => (int) $definition['slide'],
                'chart' => (int) $definition['chart'],
                'title' => config('indicators.management_report.title_labels.'.$code, $indicator->name),
                'result' => $kpi['result'] ?? null,
                'previous_result' => $previousKpi['result'] ?? null,
                'meta' => $metaLabel,
                'semaforo' => $kpi['semaforo'] ?? '-',
                'narrative' => $this->resolveNarrative(
                    $indicator,
                    $monthName,
                    $kpi['result'] ?? null,
                    $previousKpi['result'] ?? null,
                    $metaLabel,
                    $monthly['rows'] ?? collect(),
                ),
                'chart_series' => $this->monthlyChartSeries($indicator, $year),
            ];
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthName,
            'report_title' => config('indicators.management_report.cover.default_title', 'INFORME DE GESTION DE RIESGOS'),
            'indicators' => $indicators,
        ];
    }

    /**
     * @return array{numerators: array<int, float>, denominators: array<int, float>, percentages: array<int, float|null>, meta: float}
     */
    public function monthlyChartSeries(Indicator $indicator, int $year): array
    {
        $numerators = [];
        $denominators = [];
        $percentages = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthly = $this->consolidadoService->getMonthlyData($indicator, $year, $month);
            $consolidated = $monthly['consolidated'];

            if ($indicator->code === 'FT-OP-03') {
                $numerators[] = (float) ($consolidated['a']['numerator'] ?? 0);
                $denominators[] = (float) ($consolidated['a']['denominator'] ?? 0);
                $percentages[] = isset($consolidated['a']['result_percentage'])
                    ? (float) $consolidated['a']['result_percentage']
                    : null;
            } elseif ($indicator->code === 'FT-OP-06') {
                $numerators[] = (float) ($consolidated['numerator'] ?? 0);
                $denominators[] = (float) ($consolidated['denominator'] ?? 0);
                $percentages[] = (float) ($consolidated['numerator'] ?? 0);
            } else {
                $numerators[] = (float) ($consolidated['numerator'] ?? 0);
                $denominators[] = (float) ($consolidated['denominator'] ?? 0);
                $percentages[] = isset($consolidated['result_percentage'])
                    ? (float) $consolidated['result_percentage']
                    : null;
            }
        }

        return [
            'numerators' => $numerators,
            'denominators' => $denominators,
            'percentages' => $percentages,
            'meta' => (float) $indicator->target_value,
            'percentage_scale' => $indicator->code === 'FT-OP-06' ? 'count' : 'percent',
        ];
    }

    private function resolveNarrative(
        Indicator $indicator,
        string $monthName,
        ?float $result,
        ?float $previousResult,
        string $metaLabel,
        Collection $rows,
    ): string {
        $analysis = $rows
            ->pluck('analysis_text')
            ->filter(fn ($text) => is_string($text) && trim($text) !== '')
            ->map(fn (string $text) => trim($text))
            ->first();

        if (is_string($analysis) && $analysis !== '') {
            return $analysis;
        }

        return $this->narrativeBuilder->build(
            $indicator,
            $monthName,
            $result,
            $previousResult,
            $metaLabel,
        );
    }

    private function metaLabel(Indicator $indicator): string
    {
        return $indicator->metaLabel();
    }
}
