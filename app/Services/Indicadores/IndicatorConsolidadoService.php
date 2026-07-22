<?php

namespace App\Services\Indicadores;

use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\User;
use Illuminate\Support\Collection;

class IndicatorConsolidadoService
{
    public function getMonthlyData(Indicator $indicator, int $year, int $month, ?Collection $users = null): array
    {
        $users = $users ?: $this->capturableUsers();
        $period = Period::query()->where(['year' => $year, 'month' => $month])->first();

        $captures = collect();
        if ($period) {
            $captures = IndicatorCapture::query()
                ->with(['user', 'improvement'])
                ->where('indicator_id', $indicator->id)
                ->where('period_id', $period->id)
                ->get()
                ->keyBy('user_id');
        }

        $rows = $users->map(function (User $user) use ($captures, $indicator): array {
            /** @var IndicatorCapture|null $capture */
            $capture = $captures->get($user->id);
            $input = $capture?->input_data ?? [];

            return [
                'user' => $user,
                'capture' => $capture,
                'input' => $input,
                'result_percentage' => $capture?->result_percentage,
                'semaforo' => $capture ? ($capture->complies ? 'VERDE' : 'ROJO') : '-',
                'analysis_text' => $capture?->analysis_text,
                'has_improvement' => (bool) $capture?->improvement,
                'display' => $this->buildDisplayFields($indicator->code, $input),
            ];
        });

        return [
            'period' => $period,
            'rows' => $rows,
            'consolidated' => $this->buildConsolidated($indicator, $captures),
            'chart' => $this->buildChartData($indicator, $rows),
        ];
    }

    public function getQuarterlyDataFtOp08(int $year, ?Collection $users = null): array
    {
        $users = $users ?: $this->capturableUsers();
        $periods = Period::query()->where('year', $year)->whereBetween('month', [1, 12])->get()->keyBy('month');
        $captures = IndicatorCapture::query()
            ->whereHas('indicator', fn ($q) => $q->where('code', 'FT-OP-08'))
            ->whereIn('period_id', $periods->pluck('id'))
            ->get()
            ->groupBy(fn (IndicatorCapture $capture) => $capture->user_id.'-'.$capture->period?->month);

        $quarters = [
            'Q1' => [1, 2, 3],
            'Q2' => [4, 5, 6],
            'Q3' => [7, 8, 9],
            'Q4' => [10, 11, 12],
        ];

        $rows = [];
        foreach ($quarters as $quarter => $months) {
            $userRows = $users->map(function (User $user) use ($months, $periods): array {
                $num = 0.0;
                $den = 0.0;
                foreach ($months as $month) {
                    $period = $periods->get($month);
                    if (! $period) {
                        continue;
                    }
                    $capture = IndicatorCapture::query()
                        ->whereHas('indicator', fn ($q) => $q->where('code', 'FT-OP-08'))
                        ->where('user_id', $user->id)
                        ->where('period_id', $period->id)
                        ->first();
                    if ($capture) {
                        $num += (float) $capture->numerator;
                        $den += (float) $capture->denominator;
                    }
                }
                $pct = $den > 0 ? round(($num / $den) * 100, 2) : null;
                $complies = $pct !== null && $pct >= 100;

                return [
                    'user' => $user,
                    'numerator' => $num,
                    'denominator' => $den,
                    'result_percentage' => $pct,
                    'semaforo' => $pct === null ? '-' : ($complies ? 'VERDE' : 'ROJO'),
                ];
            });

            $totalNum = (float) $userRows->sum('numerator');
            $totalDen = (float) $userRows->sum('denominator');
            $totalPct = $totalDen > 0 ? round(($totalNum / $totalDen) * 100, 2) : null;

            $rows[$quarter] = [
                'zones' => $userRows,
                'consolidated' => [
                    'numerator' => $totalNum,
                    'denominator' => $totalDen,
                    'result_percentage' => $totalPct,
                    'semaforo' => $totalPct === null ? '-' : ($totalPct >= 100 ? 'VERDE' : 'ROJO'),
                ],
            ];
        }

        return $rows;
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

    private function buildConsolidated(Indicator $indicator, Collection $captures): array
    {
        if ($indicator->code === 'FT-OP-03') {
            $sumServicios = $captures->sum(fn ($c) => (float) ($c->input_data['total_servicios'] ?? 0));
            $sumSiniestros = $captures->sum(fn ($c) => (float) ($c->input_data['total_siniestros'] ?? 0));
            $sumFacturacion = $captures->sum(fn ($c) => (float) ($c->input_data['facturacion_mensual'] ?? 0));
            $sumPagado = $captures->sum(fn ($c) => (float) ($c->input_data['valor_pagado_siniestros'] ?? 0));

            $freq = $sumServicios > 0 ? round(($sumSiniestros / $sumServicios) * 100, 2) : null;
            $impacto = $sumFacturacion > 0 ? round(($sumPagado / $sumFacturacion) * 100, 2) : null;
            $cumpleA = $freq !== null && $freq <= 3;
            $cumpleB = $impacto !== null && $impacto <= 1;

            return [
                'a' => [
                    'numerator' => $sumSiniestros,
                    'denominator' => $sumServicios,
                    'result_percentage' => $freq,
                    'semaforo' => $freq === null ? '-' : ($cumpleA ? 'VERDE' : 'ROJO'),
                ],
                'b' => [
                    'numerator' => $sumPagado,
                    'denominator' => $sumFacturacion,
                    'result_percentage' => $impacto,
                    'semaforo' => $impacto === null ? '-' : ($cumpleB ? 'VERDE' : 'ROJO'),
                ],
                'final' => $cumpleA && $cumpleB ? 'VERDE' : 'ROJO',
            ];
        }

        $num = (float) $captures->sum(fn ($capture) => (float) $capture->numerator);
        $den = (float) $captures->sum(fn ($capture) => (float) $capture->denominator);
        $pct = $den > 0 ? round(($num / $den) * 100, 2) : null;
        $complies = $pct !== null ? $this->compare($pct, (float) $indicator->target_value, $indicator->target_operator) : false;

        return [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $pct,
            'semaforo' => $pct === null ? '-' : ($complies ? 'VERDE' : 'ROJO'),
        ];
    }

    private function buildDisplayFields(string $code, array $input): array
    {
        return match ($code) {
            'FT-OP-01' => ['total_personal' => $input['total_personal'] ?? null, 'personal_capacitado' => $input['personal_capacitado'] ?? null],
            'FT-OP-02' => ['total_servicios' => $input['total_servicios'] ?? null, 'no_conformes' => $input['no_conformes'] ?? null],
            'FT-OP-03' => [
                'total_servicios' => $input['total_servicios'] ?? null,
                'total_siniestros' => $input['total_siniestros'] ?? null,
                'facturacion_mensual' => $input['facturacion_mensual'] ?? null,
                'valor_pagado_siniestros' => $input['valor_pagado_siniestros'] ?? null,
            ],
            'FT-OP-04' => ['supervisiones_programadas' => $input['supervisiones_programadas'] ?? null, 'supervisiones_realizadas' => $input['supervisiones_realizadas'] ?? null],
            'FT-OP-05' => ['visitas_programadas' => $input['visitas_programadas'] ?? null, 'visitas_realizadas' => $input['visitas_realizadas'] ?? null],
            'FT-OP-06' => ['total_clientes_cadena' => $input['total_clientes_cadena'] ?? null, 'eventos_indeseables' => $input['eventos_indeseables'] ?? null],
            'FT-OP-07' => ['analisis_programados' => $input['analisis_programados'] ?? null, 'analisis_realizados' => $input['analisis_realizados'] ?? null],
            'FT-OP-08' => ['inventarios_programados' => $input['inventarios_programados'] ?? null, 'inventarios_realizados' => $input['inventarios_realizados'] ?? null],
            'FT-OP-09' => ['armas_programadas' => $input['armas_programadas'] ?? null, 'armas_inspeccionadas' => $input['armas_inspeccionadas'] ?? null],
            default => [],
        };
    }

    private function buildChartData(Indicator $indicator, Collection $rows): array
    {
        $zones = $rows->map(fn ($row) => $row['user']->name)->values()->all();
        $bars = [];
        $linePct = [];
        $lineMeta = [];

        foreach ($rows->values() as $x => $row) {
            $num = (float) ($row['capture']?->numerator ?? 0);
            $den = (float) ($row['capture']?->denominator ?? 0);
            $pct = $row['result_percentage'] !== null ? (float) $row['result_percentage'] : 0.0;
            $meta = (float) $indicator->target_value;

            $bars[] = [$x, 0, $num];
            $bars[] = [$x, 1, $den];
            $linePct[] = [$x, 2, $pct];
            $lineMeta[] = [$x, 2.5, $meta];
        }

        return [
            'zones' => $zones,
            'bars' => $bars,
            'linePct' => $linePct,
            'lineMeta' => $lineMeta,
        ];
    }

    private function compare(float $value, float $target, string $operator): bool
    {
        return match ($operator) {
            '>=' => $value >= $target,
            '<=' => $value <= $target,
            '==' => round($value, 2) === round($target, 2),
            default => false,
        };
    }
}
