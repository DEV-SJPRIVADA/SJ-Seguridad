<?php

namespace Database\Seeders;

use App\Models\Indicator;
use App\Models\IndicatorCapture;
use App\Models\Period;
use App\Models\User;
use App\Services\Indicadores\IndicatorMetricCalculator;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class IndicadorDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        Permission::findOrCreate('operations.capture', 'web');
        Permission::findOrCreate('operations.view', 'web');

        $user = User::query()->firstOrCreate(
            ['email' => 'operaciones.demo@sjseguridad.test'],
            [
                'name' => 'Operaciones Demo',
                'area_key' => 'operaciones',
                'password' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        if ($user->area_key !== 'operaciones') {
            $user->update(['area_key' => 'operaciones']);
        }

        if (! $user->hasPermissionTo('operations.capture')) {
            $user->givePermissionTo(['operations.capture', 'operations.view']);
        }

        $calculator = app(IndicatorMetricCalculator::class);
        $year = (int) config('indicators.base_year', now()->year);
        $indicators = Indicator::query()->where('is_active', true)->orderBy('code')->get();

        for ($month = 1; $month <= 12; $month++) {
            $period = Period::query()->updateOrCreate(
                ['year' => $year, 'month' => $month],
                [
                    'status' => Period::STATUS_OPEN,
                    'closed_at' => null,
                    'closed_by_user_id' => null,
                ]
            );

            foreach ($indicators as $indicator) {
                $form = $this->demoForm($indicator->code, $month);
                $metrics = $calculator->calculate($indicator, $form);

                IndicatorCapture::query()->updateOrCreate(
                    [
                        'indicator_id' => $indicator->id,
                        'user_id' => $user->id,
                        'period_id' => $period->id,
                    ],
                    [
                        'input_data' => $form,
                        'numerator' => $metrics['numerator'],
                        'denominator' => $metrics['denominator'],
                        'result_percentage' => $metrics['result_percentage'],
                        'complies' => $metrics['complies'],
                        'analysis_text' => $this->demoAnalysis($indicator->code, $month, $metrics['result_percentage']),
                        'created_by_user_id' => $user->id,
                        'updated_by_user_id' => $user->id,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function demoForm(string $code, int $month): array
    {
        return match ($code) {
            'FT-OP-01' => [
                'total_personal' => 800,
                'personal_capacitado' => (int) round(800 * min(0.98, 0.68 + ($month * 0.025))),
                'analisis_texto' => 'Capacitacion demo',
            ],
            'FT-OP-02' => [
                'total_servicios' => 1200,
                'no_conformes' => (int) round(1200 * max(0.04, 0.12 - ($month * 0.006))),
                'analisis_texto' => 'No conformidades demo',
            ],
            'FT-OP-03' => (function () use ($month): array {
                $servicios = 15000 + ($month * 200);
                $siniestros = max(1, (int) round($servicios * (0.018 + ($month * 0.0005))));
                $facturacion = 850000000 + ($month * 12000000);
                $pagado = (int) round($facturacion * (0.004 + ($month * 0.0002)));

                return [
                    'total_servicios' => $servicios,
                    'total_siniestros' => $siniestros,
                    'clasificacion_por_tipo' => [
                        ['tipo' => 'Hurto en apartamentos', 'cantidad' => $siniestros],
                    ],
                    'facturacion_mensual' => $facturacion,
                    'valor_pagado_siniestros' => $pagado,
                    'analisis_texto' => 'Siniestralidad demo',
                ];
            })(),
            'FT-OP-04' => [
                'supervisiones_programadas' => 180,
                'supervisiones_realizadas' => (int) round(180 * min(1.05, 0.82 + ($month * 0.018))),
                'analisis_texto' => 'Supervision demo',
            ],
            'FT-OP-05' => [
                'visitas_programadas' => 95,
                'visitas_realizadas' => (int) round(95 * min(1.08, 0.88 + ($month * 0.015))),
                'analisis_texto' => 'Visitas demo',
            ],
            'FT-OP-06' => [
                'total_clientes_cadena' => 420,
                'eventos_indeseables' => match ($month) {
                    1 => 2,
                    2 => 1,
                    4 => 1,
                    6 => 3,
                    7 => 1,
                    9 => 2,
                    11 => 1,
                    default => 0,
                },
                'analisis_texto' => 'Materializacion demo',
            ],
            'FT-OP-07' => [
                'analisis_programados' => 22 + ($month % 3),
                'analisis_realizados' => 22 + ($month % 3),
                'analisis_texto' => 'Analisis de riesgos demo',
            ],
            'FT-OP-08' => [
                'inventarios_programados' => 40,
                'inventarios_realizados' => in_array($month, [3, 6, 9, 12], true) ? 40 : 0,
                'analisis_texto' => 'Inventario puestos demo',
            ],
            'FT-OP-09' => [
                'armas_programadas' => 110,
                'armas_inspeccionadas' => (int) round(110 * min(1.02, 0.9 + ($month * 0.008))),
                'analisis_texto' => 'Inventario armas demo',
            ],
            default => [],
        };
    }

    private function demoAnalysis(string $code, int $month, ?float $result): string
    {
        $months = config('indicators.months', []);
        $monthName = $months[$month] ?? (string) $month;
        $resultText = $result !== null ? number_format($result, 2).'%' : 'sin dato';

        return "Datos demo {$code} para {$monthName}: resultado consolidado {$resultText}.";
    }
}
