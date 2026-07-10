<?php

namespace App\Services\Indicadores\Dashboard;

use App\Models\DashboardWeight;
use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\OperationsLeader;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ZoneDashboardService
{
    public function build(OperationsLeader $zone, int $year, int $month): array
    {
        $indicators = $this->activeIndicators();
        $weights = $this->dashboardWeights();
        $snapshot = $this->buildSnapshot($zone, $year, $month, $indicators, $weights, true);
        ['year' => $previousYear, 'month' => $previousMonth] = $this->previousPeriod($year, $month);
        $previousSnapshot = $this->buildSnapshot($zone, $previousYear, $previousMonth, $indicators, $weights, false);
        $statusBreakdown = [
            ['name' => 'Cumplen', 'value' => $snapshot['summary']['comply_count']],
            ['name' => 'No cumplen', 'value' => $snapshot['summary']['fail_count']],
            ['name' => 'Sin registro', 'value' => $snapshot['summary']['missing_count']],
        ];

        $snapshot['summary']['score_delta'] = round($snapshot['summary']['score'] - $previousSnapshot['summary']['score'], 2);
        $snapshot['summary']['coverage_delta'] = round($snapshot['summary']['coverage'] - $previousSnapshot['summary']['coverage'], 2);
        $snapshot['summary']['attention_delta'] = $snapshot['summary']['attention_count'] - $previousSnapshot['summary']['attention_count'];
        $snapshot['comparison'] = [
            'current_period_label' => $this->periodLabel($year, $month),
            'previous_period_label' => $this->periodLabel($previousYear, $previousMonth),
            'summary' => $previousSnapshot['summary'],
        ];

        return [
            'period' => $snapshot['period'],
            'summary' => $snapshot['summary'],
            'headline' => $snapshot['headline'],
            'comparison' => $snapshot['comparison'],
            'cards' => $snapshot['cards'],
            'attention' => $snapshot['attention'],
            'status_chart' => $statusBreakdown,
            'trend_chart' => $this->trendChart($zone, $year, $month, $indicators, $weights),
            'recent_activity' => $this->recentActivity($zone),
        ];
    }

    public function buildOverview(Collection $zones, int $year, int $month): array
    {
        $indicators = $this->activeIndicators();
        $weights = $this->dashboardWeights();
        ['year' => $previousYear, 'month' => $previousMonth] = $this->previousPeriod($year, $month);

        $previews = $zones->map(function (OperationsLeader $zone) use ($year, $month, $indicators, $weights): array {
            $snapshot = $this->buildSnapshot($zone, $year, $month, $indicators, $weights, false);

            return [
                'operations_leader' => $zone,
                'summary' => $snapshot['summary'],
                'headline' => $snapshot['headline'],
                'top_attention' => collect($snapshot['attention'])->take(2)->pluck('title')->all(),
                'indicator_cards' => collect($snapshot['cards'])->take(3)->all(),
                'leader_url' => route('indicadores.leaders.show', [
                    'operationsLeader' => $zone,
                    'year' => $year,
                    'month' => $month,
                ]),
            ];
        })->sortBy([
            [fn (array $row) => $row['summary']['attention_count'], 'desc'],
            [fn (array $row) => $row['summary']['score'], 'asc'],
        ])->values();

        $previousPreviews = $zones->map(function (OperationsLeader $zone) use ($previousYear, $previousMonth, $indicators, $weights): array {
            $snapshot = $this->buildSnapshot($zone, $previousYear, $previousMonth, $indicators, $weights, false);

            return [
                'operations_leader_id' => $zone->id,
                'summary' => $snapshot['summary'],
            ];
        })->keyBy('operations_leader_id');

        $zoneCount = max(1, $previews->count());
        $stableCount = $previews->where('summary.state', 'Estable')->count();
        $attentionCount = $previews->where('summary.state', 'Atencion')->count();
        $criticalCount = $previews->where('summary.state', 'Critico')->count();
        $noDataCount = $previews->where('summary.state', 'Sin datos')->count();
        $alertsCount = $previews->sum(fn (array $row) => $row['summary']['attention_count']);
        $scoreWeightTotal = max(1, $previews->sum(fn (array $row) => max(0.0, (float) $row['summary']['coverage'])));
        $avgScore = round($previews->sum(fn (array $row) => ((float) $row['summary']['score']) * max(0.0, (float) $row['summary']['coverage'])) / $scoreWeightTotal, 2);
        $avgCoverage = round($previews->avg(fn (array $row) => $row['summary']['coverage']) ?? 0, 2);
        $previousWeightTotal = max(1, $previousPreviews->sum(fn (array $row) => max(0.0, (float) $row['summary']['coverage'])));
        $previousAvgScore = round($previousPreviews->sum(fn (array $row) => ((float) $row['summary']['score']) * max(0.0, (float) $row['summary']['coverage'])) / $previousWeightTotal, 2);
        $previousAvgCoverage = round($previousPreviews->avg(fn (array $row) => $row['summary']['coverage']) ?? 0, 2);
        $previousAlertsCount = $previousPreviews->sum(fn (array $row) => $row['summary']['attention_count'] ?? 0);

        $attentionZones = $previews
            ->filter(fn (array $row) => $row['summary']['attention_count'] > 0)
            ->take(4)
            ->values()
            ->all();

        return [
            'summary' => [
                'zone_count' => $zones->count(),
                'avg_score' => $avgScore,
                'avg_coverage' => $avgCoverage,
                'score_delta' => round($avgScore - $previousAvgScore, 2),
                'coverage_delta' => round($avgCoverage - $previousAvgCoverage, 2),
                'stable_count' => $stableCount,
                'attention_count' => $attentionCount,
                'critical_count' => $criticalCount,
                'no_data_count' => $noDataCount,
                'alerts_count' => $alertsCount,
                'alerts_delta' => $alertsCount - $previousAlertsCount,
                'global_state' => $this->portfolioState($avgScore, $previews->sum(fn (array $row) => $row['summary']['comply_count'] + $row['summary']['fail_count'])),
            ],
            'headline' => $this->portfolioHeadline($zones->count(), $alertsCount, $criticalCount, $noDataCount, $month, $year),
            'comparison' => [
                'current_period_label' => $this->periodLabel($year, $month),
                'previous_period_label' => $this->periodLabel($previousYear, $previousMonth),
                'summary' => [
                    'avg_score' => $previousAvgScore,
                    'avg_coverage' => $previousAvgCoverage,
                    'alerts_count' => $previousAlertsCount,
                ],
            ],
            'attention_zones' => $attentionZones,
            'zone_cards' => $previews->all(),
            'recent_activity' => $this->recentActivityAcrossZones($zones),
            'alerts_per_zone' => [
                'labels' => $previews->pluck('operations_leader.code')->all(),
                'values' => $previews->pluck('summary.attention_count')->all(),
            ],
        ];
    }

    private function buildSnapshot(
        OperationsLeader $zone,
        int $year,
        int $month,
        Collection $indicators,
        Collection $weights,
        bool $includeExtendedCards
    ): array {
        $period = Period::query()->where(['year' => $year, 'month' => $month])->first();
        $captures = collect();

        if ($period) {
            $captures = IndicatorCapture::query()
                ->with(['updatedBy', 'period'])
                ->where('operations_leader_id', $zone->id)
                ->where('period_id', $period->id)
                ->whereIn('indicator_id', $indicators->pluck('id'))
                ->get()
                ->keyBy('indicator_id');
        }

        $cards = [];
        $attention = [];
        $weightedScore = 0.0;
        $complyCount = 0;
        $failCount = 0;
        $missingCount = 0;

        foreach ($indicators as $indicator) {
            /** @var IndicatorCapture|null $capture */
            $capture = $captures->get($indicator->id);
            $weight = (float) ($weights[$indicator->id]->weight ?? 0);
            $indicatorUrl = route('indicators.show', [
                'indicator' => $indicator->code,
                'year' => $year,
                'month' => $month,
                'operations_leader_id' => $zone->id,
            ]);

            if (! $capture) {
                $missingCount++;
                $cards[] = [
                    'indicator' => $indicator,
                    'status_key' => 'missing',
                    'status_label' => 'Sin registro',
                    'status_tone' => 'slate',
                    'semaforo' => 'SIN REGISTRO',
                    'result_label' => 'Sin captura',
                    'detail_label' => 'Registra el mes para generar el estado.',
                    'meta_label' => $this->metaLabel($indicator),
                    'weight' => $weight,
                    'score' => 0.0,
                    'updated_at_label' => 'Sin actividad',
                    'updated_by_name' => null,
                    'indicator_url' => $indicatorUrl,
                ];

                $attention[] = [
                    'priority' => 1,
                    'indicator' => $indicator,
                    'title' => $indicator->code.' - '.$indicator->name,
                    'issue' => 'No hay captura registrada para este periodo.',
                    'action' => 'Registrar datos del mes.',
                    'tone' => 'amber',
                    'indicator_url' => $indicatorUrl,
                ];

                continue;
            }

            $metrics = $this->captureMetrics($indicator, $capture);
            $weightedScore += ($metrics['score'] * $weight) / 100;

            if ($metrics['complies']) {
                $complyCount++;
            } else {
                $failCount++;
                $attention[] = [
                    'priority' => 2,
                    'indicator' => $indicator,
                    'title' => $indicator->code.' - '.$indicator->name,
                    'issue' => 'El indicador no cumple la meta del periodo.',
                    'action' => 'Revisar analisis y ajustar acciones.',
                    'tone' => 'rose',
                    'indicator_url' => $indicatorUrl,
                ];
            }

            $cards[] = [
                'indicator' => $indicator,
                'status_key' => $metrics['complies'] ? 'ok' : 'alert',
                'status_label' => $metrics['complies'] ? 'Cumple' : 'No cumple',
                'status_tone' => $metrics['complies'] ? 'emerald' : 'rose',
                'semaforo' => $metrics['complies'] ? 'VERDE' : 'ROJO',
                'result_label' => $metrics['result_label'],
                'detail_label' => $metrics['detail_label'],
                'meta_label' => $metrics['meta_label'],
                'weight' => $weight,
                'score' => $metrics['score'],
                'updated_at_label' => $capture->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                'updated_by_name' => $capture->updatedBy?->name,
                'indicator_url' => $indicatorUrl,
            ];
        }

        $cards = collect($cards)->sortBy('indicator.code')->values();
        $attention = collect($attention)
            ->sortBy([
                ['priority', 'asc'],
                ['indicator.code', 'asc'],
            ])
            ->values();

        $totalIndicators = max(1, $indicators->count());
        $coverage = round((($complyCount + $failCount) / $totalIndicators) * 100, 2);
        $globalScore = round($weightedScore, 2);

        return [
            'period' => $period,
            'summary' => [
                'leader_name' => $zone->name,
                'score' => $globalScore,
                'state' => $this->scoreState($globalScore, $complyCount + $failCount),
                'comply_count' => $complyCount,
                'fail_count' => $failCount,
                'missing_count' => $missingCount,
                'attention_count' => $failCount + $missingCount,
                'coverage' => $coverage,
            ],
            'headline' => $this->headline($zone, $complyCount, $failCount, $missingCount, $period),
            'cards' => $includeExtendedCards ? $cards->all() : $cards->take(3)->all(),
            'attention' => $attention->all(),
        ];
    }

    private function activeIndicators(): Collection
    {
        return Indicator::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    private function dashboardWeights(): Collection
    {
        return DashboardWeight::query()->get()->keyBy('indicator_id');
    }

    private function recentActivityAcrossZones(Collection $zones): array
    {
        if ($zones->isEmpty()) {
            return [];
        }

        return IndicatorCapture::query()
            ->with(['indicator', 'period', 'updatedBy', 'operationsLeader'])
            ->whereIn('operations_leader_id', $zones->pluck('id'))
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(function (IndicatorCapture $capture): array {
                $periodLabel = $capture->period
                    ? sprintf('%04d-%02d', $capture->period->year, $capture->period->month)
                    : 'Sin periodo';

                return [
                    'leader_name' => $capture->operationsLeader?->name ?? 'Jefe',
                    'title' => $capture->indicator?->code.' - '.$capture->indicator?->name,
                    'period_label' => $periodLabel,
                    'result_label' => $this->captureMetrics($capture->indicator, $capture)['result_label'],
                    'updated_at_label' => $capture->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    'updated_by_name' => $capture->updatedBy?->name ?? 'Sin usuario',
                    'leader_url' => route('indicadores.leaders.show', [
                        'operations_leader' => $capture->operations_leader_id,
                        'year' => $capture->period?->year ?? now()->year,
                        'month' => $capture->period?->month ?? now()->month,
                    ]),
                ];
            })
            ->all();
    }

    private function recentActivity(OperationsLeader $zone): array
    {
        return IndicatorCapture::query()
            ->with(['indicator', 'period', 'updatedBy'])
            ->where('operations_leader_id', $zone->id)
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(function (IndicatorCapture $capture) use ($zone): array {
                $periodLabel = $capture->period
                    ? sprintf('%04d-%02d', $capture->period->year, $capture->period->month)
                    : 'Sin periodo';

                return [
                    'title' => $capture->indicator?->code.' - '.$capture->indicator?->name,
                    'period_label' => $periodLabel,
                    'result_label' => $this->captureMetrics($capture->indicator, $capture)['result_label'],
                    'updated_at_label' => $capture->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    'updated_by_name' => $capture->updatedBy?->name ?? 'Sin usuario',
                    'indicator_url' => route('indicators.show', [
                        'indicator' => $capture->indicator?->code,
                        'year' => $capture->period?->year ?? now()->year,
                        'month' => $capture->period?->month ?? now()->month,
                        'operations_leader_id' => $zone->id,
                    ]),
                ];
            })
            ->all();
    }

    private function trendChart(
        OperationsLeader $zone,
        int $year,
        int $month,
        Collection $indicators,
        Collection $weights
    ): array {
        $months = [];
        $scores = [];
        $coverage = [];

        $cursor = Carbon::create($year, $month, 1);

        for ($i = 5; $i >= 0; $i--) {
            $point = $cursor->copy()->subMonths($i);
            $snapshot = $this->scoreForMonth($zone, (int) $point->year, (int) $point->month, $indicators, $weights);

            $months[] = $this->monthShort((int) $point->month).' '.$point->format('y');
            $scores[] = $snapshot['score'];
            $coverage[] = $snapshot['coverage'];
        }

        return [
            'months' => $months,
            'scores' => $scores,
            'coverage' => $coverage,
        ];
    }

    private function scoreForMonth(OperationsLeader $zone, int $year, int $month, Collection $indicators, Collection $weights): array
    {
        $period = Period::query()->where(['year' => $year, 'month' => $month])->first();
        if (! $period) {
            return ['score' => 0.0, 'coverage' => 0.0];
        }

        $captures = IndicatorCapture::query()
            ->where('operations_leader_id', $zone->id)
            ->where('period_id', $period->id)
            ->whereIn('indicator_id', $indicators->pluck('id'))
            ->get()
            ->keyBy('indicator_id');

        $score = 0.0;
        foreach ($indicators as $indicator) {
            /** @var IndicatorCapture|null $capture */
            $capture = $captures->get($indicator->id);
            if (! $capture) {
                continue;
            }

            $weight = (float) ($weights[$indicator->id]->weight ?? 0);
            $score += ($this->captureMetrics($indicator, $capture)['score'] * $weight) / 100;
        }

        $coverage = round(($captures->count() / max(1, $indicators->count())) * 100, 2);

        return [
            'score' => round($score, 2),
            'coverage' => $coverage,
        ];
    }

    private function captureMetrics(Indicator $indicator, IndicatorCapture $capture): array
    {
        if ($indicator->code === 'FT-OP-03') {
            $data = $capture->input_data ?? [];
            $totalServicios = (float) ($data['total_servicios'] ?? 0);
            $totalSiniestros = (float) ($data['total_siniestros'] ?? 0);
            $facturacion = (float) ($data['facturacion_mensual'] ?? 0);
            $valorPagado = (float) ($data['valor_pagado_siniestros'] ?? 0);

            $frecuencia = $totalServicios > 0 ? round(($totalSiniestros / $totalServicios) * 100, 2) : 0.0;
            $impacto = $facturacion > 0 ? round(($valorPagado / $facturacion) * 100, 2) : 0.0;

            $freqScore = $this->normalizeByOperator($frecuencia, 3, '<=');
            $impactScore = $this->normalizeByOperator($impacto, 1, '<=');
            $score = round(($freqScore + $impactScore) / 2, 2);

            return [
                'complies' => (bool) $capture->complies,
                'score' => $score,
                'result_label' => 'F '.$this->percent($frecuencia).' | I '.$this->percent($impacto),
                'detail_label' => 'Frecuencia / impacto economico del mes.',
                'meta_label' => 'A<=3% y B<=1%',
            ];
        }

        $result = (float) $capture->result_percentage;

        return [
            'complies' => (bool) $capture->complies,
            'score' => $this->normalizeByOperator($result, (float) $indicator->target_value, $indicator->target_operator),
            'result_label' => $this->percent($result),
            'detail_label' => 'Meta '.$indicator->target_operator.' '.$indicator->target_value.'%',
            'meta_label' => $this->metaLabel($indicator),
        ];
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

    private function metaLabel(Indicator $indicator): string
    {
        if ($indicator->code === 'FT-OP-03') {
            return 'A<=3% y B<=1%';
        }

        return $indicator->target_operator.$indicator->target_value.'%';
    }

    private function headline(OperationsLeader $zone, int $complyCount, int $failCount, int $missingCount, ?Period $period): string
    {
        $periodText = $period
            ? sprintf('%s %d', $this->monthName((int) $period->month), (int) $period->year)
            : 'el periodo seleccionado';

        if ($missingCount > 0) {
            return sprintf(
                '%s tiene %d indicadores sin registro en %s. La prioridad operativa es completar la captura.',
                $zone->name,
                $missingCount,
                $periodText
            );
        }

        if ($failCount > 0) {
            return sprintf(
                '%s presenta %d indicadores en alerta en %s. Revisa primero los que no cumplen.',
                $zone->name,
                $failCount,
                $periodText
            );
        }

        if ($complyCount > 0) {
            return sprintf(
                '%s mantiene sus indicadores capturados en %s sin alertas activas.',
                $zone->name,
                $periodText
            );
        }

        return sprintf(
            '%s aun no registra capturas para %s. Usa este tablero como punto de partida operativo.',
            $zone->name,
            $periodText
        );
    }

    private function scoreState(float $score, int $registeredCount): string
    {
        if ($registeredCount === 0) {
            return 'Sin datos';
        }

        if ($score >= 90) {
            return 'Estable';
        }

        if ($score >= 75) {
            return 'Atencion';
        }

        return 'Critico';
    }

    private function portfolioState(float $score, int $registeredCount): string
    {
        return $this->scoreState($score, $registeredCount);
    }

    private function portfolioHeadline(int $zoneCount, int $alertsCount, int $criticalCount, int $noDataCount, int $month, int $year): string
    {
        $periodText = sprintf('%s %d', $this->monthName($month), $year);

        if ($zoneCount === 0) {
            return 'No hay zonas asignadas para este usuario.';
        }

        if ($noDataCount === $zoneCount) {
            return sprintf('Ninguna zona tiene capturas registradas para %s.', $periodText);
        }

        if ($criticalCount > 0) {
            return sprintf(
                'Hay %d zonas en estado critico y %d alertas activas en %s. La prioridad es intervenir esas zonas primero.',
                $criticalCount,
                $alertsCount,
                $periodText
            );
        }

        if ($alertsCount > 0) {
            return sprintf(
                'Hay %d alertas abiertas distribuidas en las zonas disponibles para %s.',
                $alertsCount,
                $periodText
            );
        }

        return sprintf(
            'Las zonas asignadas mantienen un comportamiento estable en %s.',
            $periodText
        );
    }

    private function previousPeriod(int $year, int $month): array
    {
        $date = Carbon::create($year, $month, 1)->subMonth();

        return [
            'year' => (int) $date->year,
            'month' => (int) $date->month,
        ];
    }

    private function periodLabel(int $year, int $month): string
    {
        return sprintf('%s %d', $this->monthName($month), $year);
    }

    private function percent(float $value): string
    {
        return number_format($value, 2, '.', ',').'%';
    }

    private function monthName(int $month): string
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ][$month] ?? 'Mes';
    }

    private function monthShort(int $month): string
    {
        return [
            1 => 'ENE',
            2 => 'FEB',
            3 => 'MAR',
            4 => 'ABR',
            5 => 'MAY',
            6 => 'JUN',
            7 => 'JUL',
            8 => 'AGO',
            9 => 'SEP',
            10 => 'OCT',
            11 => 'NOV',
            12 => 'DIC',
        ][$month] ?? 'MES';
    }
}
