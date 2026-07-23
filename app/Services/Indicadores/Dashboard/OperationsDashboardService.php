<?php

namespace App\Services\Indicadores\Dashboard;

use App\Models\DashboardWeight;
use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\User;
use App\Services\Indicadores\IndicatorConsolidadoService;
use Illuminate\Support\Collection;

class OperationsDashboardService
{
    public function __construct(private readonly IndicatorConsolidadoService $consolidadoService)
    {
    }

    public function build(int $year, int $month): array
    {
        return $this->buildCore($year, $month, true);
    }

    private function buildCore(int $year, int $month, bool $withTrends): array
    {
        $indicators = Indicator::query()->where('is_active', true)->orderBy('code')->get();
        $weights = DashboardWeight::query()->get()->keyBy('indicator_id');
        $users = $this->capturableUsers();
        [$previousYear, $previousMonth] = $this->previousPeriod($year, $month);
        $previousPeriodLabel = config('indicators.months')[$previousMonth] ?? (string) $previousMonth;

        $kpis = [];
        $weightedAccumulator = 0.0;
        $criticalRows = [];

        foreach ($indicators as $indicator) {
            $monthly = $this->consolidadoService->getMonthlyData($indicator, $year, $month, $users);
            $previousMonthly = $this->consolidadoService->getMonthlyData($indicator, $previousYear, $previousMonth, $users);
            $result = $this->resultForIndicator($indicator, $monthly['consolidated']);
            $previousResult = $this->resultForIndicator($indicator, $previousMonthly['consolidated']);
            $normalized = $this->normalizeIndicator($indicator, $monthly['consolidated'], $result);
            $weight = (float) ($weights[$indicator->id]->weight ?? 0);
            $weightedAccumulator += ($normalized * $weight) / 100;

            $zonesRed = collect($monthly['rows'])->filter(fn ($row) => $row['semaforo'] === 'ROJO')->count();
            $hasImprovements = collect($monthly['rows'])->contains(fn ($row) => $row['semaforo'] === 'ROJO' && $row['has_improvement']);

            $deviation = $this->deviationForIndicator($indicator, $monthly['consolidated']);
            $criticality = $deviation + ($zonesRed * 10);

            $kpis[] = [
                'indicator' => $indicator,
                'result' => $result,
                'previous_result' => $previousResult,
                'meta' => $this->metaLabel($indicator),
                'semaforo' => $this->semaforoByNormalized($normalized),
                'has_improvements' => $hasImprovements,
                'consolidado_url' => route('indicadores.admin.consolidado.show', ['indicator' => $indicator->code, 'year' => $year, 'month' => $month]),
                'normalized' => $normalized,
                'weight' => $weight,
                'zones_red' => $zonesRed,
                'criticality' => $criticality,
            ];

            $criticalRows[] = [
                'indicator' => $indicator,
                'result' => $result,
                'meta' => $this->metaLabel($indicator),
                'zones_red' => $zonesRed,
                'deviation' => $deviation,
                'criticality' => $criticality,
                'consolidado_url' => route('indicadores.admin.consolidado.show', ['indicator' => $indicator->code, 'year' => $year, 'month' => $month]),
            ];
        }

        $globalScore = round($weightedAccumulator, 2);

        $zoneRanking = $this->zoneRanking($year, $month, $indicators, $weights, $users);
        $criticalRanking = collect($criticalRows)->sortByDesc('criticality')->values()->all();
        $trends = $withTrends ? $this->trends($year, $month) : ['months' => [], 'global' => [], 'indicators' => []];

        return [
            'kpis' => $kpis,
            'previous_period' => [
                'year' => $previousYear,
                'month' => $previousMonth,
                'label' => $previousPeriodLabel,
            ],
            'global_score' => $globalScore,
            'global_state' => $this->globalState($globalScore),
            'zone_ranking' => $zoneRanking,
            'critical_ranking' => $criticalRanking,
            'trends' => $trends,
        ];
    }

    public function zoneSummary(int $year, int $month, User $user): array
    {
        $period = Period::query()->where(['year' => $year, 'month' => $month])->first();
        $indicators = Indicator::query()->where('is_active', true)->orderBy('code')->get();

        return $indicators->map(function (Indicator $indicator) use ($user, $period): array {
            $capture = null;
            if ($period) {
                $capture = IndicatorCapture::query()
                    ->where('indicator_id', $indicator->id)
                    ->where('user_id', $user->id)
                    ->where('period_id', $period->id)
                    ->first();
            }

            $semaforo = $capture ? ($capture->complies ? 'VERDE' : 'ROJO') : '-';

            return [
                'indicator' => $indicator,
                'result' => $capture?->result_percentage,
                'semaforo' => $semaforo,
            ];
        })->all();
    }

    /**
     * @return Collection<int, User>
     */
    private function capturableUsers(): Collection
    {
        return User::permission(['operations.capture', 'operations.manage'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function zoneRanking(int $year, int $month, Collection $indicators, Collection $weights, Collection $users): array
    {
        $period = Period::query()->where(['year' => $year, 'month' => $month])->first();
        if (! $period) {
            return $users->map(fn (User $user) => ['user' => $user, 'score' => 0.0, 'red_count' => 0])->all();
        }

        $captures = IndicatorCapture::query()
            ->where('period_id', $period->id)
            ->whereIn('indicator_id', $indicators->pluck('id'))
            ->whereIn('user_id', $users->pluck('id'))
            ->get()
            ->keyBy(fn (IndicatorCapture $capture) => $capture->indicator_id.'-'.$capture->user_id);

        $ranking = [];
        foreach ($users as $user) {
            $score = 0.0;
            $red = 0;
            foreach ($indicators as $indicator) {
                /** @var IndicatorCapture|null $capture */
                $capture = $captures->get($indicator->id.'-'.$user->id);
                if (! $capture) {
                    continue;
                }

                $normalized = $this->normalizeCapture($indicator, $capture);
                $weight = (float) ($weights[$indicator->id]->weight ?? 0);
                $score += ($normalized * $weight) / 100;
                if (! $capture->complies) {
                    $red++;
                }
            }
            $ranking[] = ['user' => $user, 'score' => round($score, 2), 'red_count' => $red];
        }

        return collect($ranking)->sortByDesc('score')->values()->all();
    }

    private function trends(int $year, int $month): array
    {
        $months = [];
        $global = [];
        $byIndicator = [
            'FT-OP-03' => [],
            'FT-OP-09' => [],
            'FT-OP-02' => [],
        ];

        $date = \Carbon\Carbon::create($year, $month, 1);
        for ($i = 11; $i >= 0; $i--) {
            $d = $date->copy()->subMonths($i);
            $y = (int) $d->year;
            $m = (int) $d->month;
            $months[] = $d->format('Y-m');

            $snapshot = $this->buildCore($y, $m, false);
            $global[] = $snapshot['global_score'];

            foreach (array_keys($byIndicator) as $code) {
                $kpi = collect($snapshot['kpis'])->firstWhere('indicator.code', $code);
                $byIndicator[$code][] = $kpi['result'] ?? null;
            }
        }

        return [
            'months' => $months,
            'global' => $global,
            'indicators' => $byIndicator,
        ];
    }

    private function resultForIndicator(Indicator $indicator, array $consolidated): ?float
    {
        if ($indicator->code === 'FT-OP-03') {
            $a = $consolidated['a']['result_percentage'] ?? null;
            $b = $consolidated['b']['result_percentage'] ?? null;
            if ($a === null || $b === null) {
                return null;
            }

            return round(($a + $b) / 2, 2);
        }

        return $consolidated['result_percentage'] ?? null;
    }

    private function normalizeIndicator(Indicator $indicator, array $consolidated, ?float $result): float
    {
        if ($indicator->code === 'FT-OP-03') {
            $a = (float) ($consolidated['a']['result_percentage'] ?? 0);
            $b = (float) ($consolidated['b']['result_percentage'] ?? 0);
            $nA = $this->normalizeByOperator($a, 3, '<=');
            $nB = $this->normalizeByOperator($b, 1, '<=');

            return round(($nA + $nB) / 2, 2);
        }

        if ($result === null) {
            return 0.0;
        }

        return $this->normalizeByOperator($result, (float) $indicator->target_value, $indicator->target_operator);
    }

    private function normalizeCapture(Indicator $indicator, IndicatorCapture $capture): float
    {
        if ($indicator->code === 'FT-OP-03') {
            $data = $capture->input_data ?? [];
            $servicios = (float) ($data['total_servicios'] ?? 0);
            $siniestros = (float) ($data['total_siniestros'] ?? 0);
            $fact = (float) ($data['facturacion_mensual'] ?? 0);
            $pag = (float) ($data['valor_pagado_siniestros'] ?? 0);
            $freq = $servicios > 0 ? round(($siniestros / $servicios) * 100, 2) : 0;
            $impact = $fact > 0 ? round(($pag / $fact) * 100, 2) : 0;

            return round(($this->normalizeByOperator($freq, 3, '<=') + $this->normalizeByOperator($impact, 1, '<=')) / 2, 2);
        }

        return $this->normalizeByOperator((float) $capture->result_percentage, (float) $indicator->target_value, $indicator->target_operator);
    }

    private function normalizeByOperator(float $result, float $meta, string $operator): float
    {
        if ($operator === '>=') {
            if ($meta <= 0) {
                return 0.0;
            }
            $ratio = min(max($result / $meta, 0), 1.5);

            return round($ratio * 100, 2);
        }

        if ($operator === '<=') {
            if ($result <= $meta) {
                return 100.0;
            }
            if ($meta <= 0) {
                return 0.0;
            }
            $score = 100 - ((($result - $meta) / $meta) * 100);

            return round(max(0, $score), 2);
        }

        if ($operator === '==') {
            if ($meta == 0.0) {
                return $result == 0.0 ? 100.0 : 0.0;
            }

            return round($result == $meta ? 100.0 : 0.0, 2);
        }

        return 0.0;
    }

    private function semaforoByNormalized(float $normalized): string
    {
        return $normalized >= 100 ? 'VERDE' : 'ROJO';
    }

    private function globalState(float $score): string
    {
        if ($score >= 90) {
            return 'ESTABLE';
        }
        if ($score >= 75) {
            return 'ATENCION';
        }

        return 'CRITICO';
    }

    private function deviationForIndicator(Indicator $indicator, array $consolidated): float
    {
        if ($indicator->code === 'FT-OP-03') {
            $a = (float) ($consolidated['a']['result_percentage'] ?? 0);
            $b = (float) ($consolidated['b']['result_percentage'] ?? 0);

            return max(0, $a - (float) $indicator->target_value) + max(0, $b - (float) ($indicator->critical_value ?? 1));
        }

        $result = (float) ($consolidated['result_percentage'] ?? 0);
        $meta = (float) $indicator->target_value;

        return match ($indicator->target_operator) {
            '>=' => max(0, $meta - $result),
            '<=' => max(0, $result - $meta),
            '==' => $meta == 0 ? ($result > 0 ? $result : 0) : ($result == $meta ? 0 : abs($result - $meta)),
            default => 0,
        };
    }

    private function metaLabel(Indicator $indicator): string
    {
        return $indicator->metaLabel();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function previousPeriod(int $year, int $month): array
    {
        $date = \Carbon\Carbon::create($year, $month, 1)->subMonth();

        return [(int) $date->year, (int) $date->month];
    }
}
