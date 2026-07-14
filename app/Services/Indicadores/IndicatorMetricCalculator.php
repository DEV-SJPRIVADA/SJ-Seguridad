<?php

namespace App\Services\Indicadores;

use App\Models\Indicator;

class IndicatorMetricCalculator
{
    /**
     * @return array<string, mixed>
     */
    public function defaultForm(string $code): array
    {
        return match ($code) {
            'FT-OP-01' => ['total_personal' => null, 'personal_capacitado' => null],
            'FT-OP-02' => ['total_servicios' => null, 'no_conformes' => null],
            'FT-OP-03' => [
                'total_servicios' => null,
                'total_siniestros' => null,
                'clasificacion_por_tipo' => [['tipo' => '', 'cantidad' => null]],
                'facturacion_mensual' => null,
                'valor_pagado_siniestros' => null,
            ],
            'FT-OP-04' => ['supervisiones_programadas' => null, 'supervisiones_realizadas' => null],
            'FT-OP-05' => ['visitas_programadas' => null, 'visitas_realizadas' => null],
            'FT-OP-06' => ['total_clientes_cadena' => null, 'eventos_indeseables' => null],
            'FT-OP-07' => ['analisis_programados' => null, 'analisis_realizados' => null],
            'FT-OP-08' => ['inventarios_programados' => null, 'inventarios_realizados' => null],
            'FT-OP-09' => ['armas_programadas' => null, 'armas_inspeccionadas' => null],
            default => [],
        };
    }

    /**
     * @return array<string, list<string>>
     */
    public function fieldRules(string $code, array $form = []): array
    {
        $rules = match ($code) {
            'FT-OP-01' => [
                'form.total_personal' => ['required', 'numeric', 'min:0.01'],
                'form.personal_capacitado' => ['required', 'numeric', 'min:0'],
            ],
            'FT-OP-02' => [
                'form.total_servicios' => ['required', 'numeric', 'min:0.01'],
                'form.no_conformes' => ['required', 'numeric', 'min:0'],
            ],
            'FT-OP-03' => [
                'form.total_servicios' => ['required', 'numeric', 'min:0.01'],
                'form.total_siniestros' => ['required', 'numeric', 'min:0'],
                'form.facturacion_mensual' => ['required', 'numeric', 'min:0.01'],
                'form.valor_pagado_siniestros' => ['required', 'numeric', 'min:0'],
            ],
            'FT-OP-04' => [
                'form.supervisiones_programadas' => ['required', 'numeric', 'min:0.01'],
                'form.supervisiones_realizadas' => ['required', 'numeric', 'min:0'],
            ],
            'FT-OP-05' => [
                'form.visitas_programadas' => ['required', 'numeric', 'min:0.01'],
                'form.visitas_realizadas' => ['required', 'numeric', 'min:0'],
            ],
            'FT-OP-06' => [
                'form.total_clientes_cadena' => ['required', 'numeric', 'min:0.01'],
                'form.eventos_indeseables' => ['required', 'numeric', 'min:0'],
            ],
            'FT-OP-07' => [
                'form.analisis_programados' => ['required', 'numeric', 'min:0.01'],
                'form.analisis_realizados' => ['required', 'numeric', 'min:0'],
            ],
            'FT-OP-08' => [
                'form.inventarios_programados' => ['required', 'numeric', 'min:0.01'],
                'form.inventarios_realizados' => ['required', 'numeric', 'min:0'],
            ],
            'FT-OP-09' => [
                'form.armas_programadas' => ['required', 'numeric', 'min:0.01'],
                'form.armas_inspeccionadas' => ['required', 'numeric', 'min:0'],
            ],
            default => [],
        };

        if ($code === 'FT-OP-03' && (float) ($form['total_siniestros'] ?? 0) >= 1) {
            $rules['form.clasificacion_por_tipo'] = ['required', 'array', 'min:1'];
            $rules['form.clasificacion_por_tipo.*.tipo'] = ['required', 'string'];
            $rules['form.clasificacion_por_tipo.*.cantidad'] = ['required', 'numeric', 'min:0'];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array{numerator: float, denominator: float, result_percentage: float, complies: bool, errors: list<string>}
     */
    public function calculate(Indicator $indicator, array $form): array
    {
        return match ($indicator->code) {
            'FT-OP-01' => $this->ratioGe($form, 'total_personal', 'personal_capacitado', (float) $indicator->target_value),
            'FT-OP-02' => $this->ratioLe($form, 'total_servicios', 'no_conformes', (float) $indicator->target_value),
            'FT-OP-03' => $this->calculateFtOp03($form),
            'FT-OP-04' => $this->ratioGe($form, 'supervisiones_programadas', 'supervisiones_realizadas', 90.0),
            'FT-OP-05' => $this->ratioGe($form, 'visitas_programadas', 'visitas_realizadas', 100.0),
            'FT-OP-06' => $this->ratioEqZero($form, 'total_clientes_cadena', 'eventos_indeseables'),
            'FT-OP-07' => $this->ratioGe($form, 'analisis_programados', 'analisis_realizados', 100.0),
            'FT-OP-08' => $this->ratioGe($form, 'inventarios_programados', 'inventarios_realizados', 100.0),
            'FT-OP-09' => $this->ratioGe($form, 'armas_programadas', 'armas_inspeccionadas', 100.0),
            default => [
                'numerator' => 0.0,
                'denominator' => 0.0,
                'result_percentage' => 0.0,
                'complies' => false,
                'errors' => ['Indicador no implementado.'],
            ],
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function sheetLabels(Indicator $indicator): array
    {
        if ($indicator->code === 'FT-OP-01') {
            return ['TOTAL PERSONAL OPERATIVO', 'PERSONAL OPERATIVO CAPACITADO POR ZONA'];
        }

        $fields = collect($indicator->required_fields ?? [])
            ->filter(fn ($field) => $field !== 'analisis_texto')
            ->values();

        $denominator = strtoupper(trim(str_replace('_', ' ', (string) ($fields->get(0) ?? 'total_base'))));
        $numerator = strtoupper(trim(str_replace('_', ' ', (string) ($fields->get(1) ?? 'total_cumplido'))));

        return [$denominator, $numerator];
    }

    public function fieldsPartial(string $code): string
    {
        return match ($code) {
            'FT-OP-01' => 'areas.operaciones.indicadores.partials.ft-op-01',
            'FT-OP-02' => 'areas.operaciones.indicadores.partials.ft-op-02',
            'FT-OP-03' => 'areas.operaciones.indicadores.partials.ft-op-03',
            'FT-OP-04' => 'areas.operaciones.indicadores.partials.ft-op-04',
            'FT-OP-05' => 'areas.operaciones.indicadores.partials.ft-op-05',
            'FT-OP-06' => 'areas.operaciones.indicadores.partials.ft-op-06',
            'FT-OP-07' => 'areas.operaciones.indicadores.partials.ft-op-07',
            'FT-OP-08' => 'areas.operaciones.indicadores.partials.ft-op-08',
            'FT-OP-09' => 'areas.operaciones.indicadores.partials.ft-op-09',
            default => '',
        };
    }

    /**
     * @return list<string>
     */
    public function siniestroOptions(): array
    {
        return [
            'Hurto en apartamentos',
            'Hurto de accesorios de vehiculos',
            'Hurto de vehiculos / motos',
            'Hurto de elementos, dinero, bicicletas, electronicos, encomiendas fuera de los apartamentos',
            'Otros / afectaciones economicas',
        ];
    }

    /**
     * Client-side formula metadata for JS preview.
     *
     * @return array{type: string, den: string, num: string, threshold?: float, operator?: string}
     */
    public function clientFormula(string $code, Indicator $indicator): array
    {
        return match ($code) {
            'FT-OP-01' => ['type' => 'ratio_ge', 'den' => 'total_personal', 'num' => 'personal_capacitado', 'threshold' => (float) $indicator->target_value],
            'FT-OP-02' => ['type' => 'ratio_le', 'den' => 'total_servicios', 'num' => 'no_conformes', 'threshold' => (float) $indicator->target_value],
            'FT-OP-03' => ['type' => 'ft_op_03', 'den' => 'total_servicios', 'num' => 'total_siniestros'],
            'FT-OP-04' => ['type' => 'ratio_ge', 'den' => 'supervisiones_programadas', 'num' => 'supervisiones_realizadas', 'threshold' => 90.0],
            'FT-OP-05' => ['type' => 'ratio_ge', 'den' => 'visitas_programadas', 'num' => 'visitas_realizadas', 'threshold' => 100.0],
            'FT-OP-06' => ['type' => 'ratio_eq_zero', 'den' => 'total_clientes_cadena', 'num' => 'eventos_indeseables'],
            'FT-OP-07' => ['type' => 'ratio_ge', 'den' => 'analisis_programados', 'num' => 'analisis_realizados', 'threshold' => 100.0],
            'FT-OP-08' => ['type' => 'ratio_ge', 'den' => 'inventarios_programados', 'num' => 'inventarios_realizados', 'threshold' => 100.0],
            'FT-OP-09' => ['type' => 'ratio_ge', 'den' => 'armas_programadas', 'num' => 'armas_inspeccionadas', 'threshold' => 100.0],
            default => ['type' => 'none', 'den' => '', 'num' => ''],
        };
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array{numerator: float, denominator: float, result_percentage: float, complies: bool, errors: list<string>}
     */
    private function ratioGe(array $form, string $denKey, string $numKey, float $threshold): array
    {
        $den = (float) ($form[$denKey] ?? 0);
        $num = (float) ($form[$numKey] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = "{$denKey} no puede ser 0.";
        }
        $result = $den > 0 ? round(($num / $den) * 100, 2) : 0.0;

        return [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $result,
            'complies' => $den > 0 && $result >= $threshold,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array{numerator: float, denominator: float, result_percentage: float, complies: bool, errors: list<string>}
     */
    private function ratioLe(array $form, string $denKey, string $numKey, float $threshold): array
    {
        $den = (float) ($form[$denKey] ?? 0);
        $num = (float) ($form[$numKey] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = "{$denKey} no puede ser 0.";
        }
        $result = $den > 0 ? round(($num / $den) * 100, 2) : 0.0;

        return [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $result,
            'complies' => $den > 0 && $result <= $threshold,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array{numerator: float, denominator: float, result_percentage: float, complies: bool, errors: list<string>}
     */
    private function ratioEqZero(array $form, string $denKey, string $numKey): array
    {
        $den = (float) ($form[$denKey] ?? 0);
        $num = (float) ($form[$numKey] ?? 0);
        $errors = [];
        if ($den <= 0) {
            $errors[] = "{$denKey} no puede ser 0.";
        }
        $result = $den > 0 ? round(($num / $den) * 100, 2) : 0.0;

        return [
            'numerator' => $num,
            'denominator' => $den,
            'result_percentage' => $result,
            'complies' => $den > 0 && $result == 0.0,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array{numerator: float, denominator: float, result_percentage: float, complies: bool, errors: list<string>}
     */
    private function calculateFtOp03(array $form): array
    {
        $totalServicios = (float) ($form['total_servicios'] ?? 0);
        $totalSiniestros = (float) ($form['total_siniestros'] ?? 0);
        $facturacion = (float) ($form['facturacion_mensual'] ?? 0);
        $valorPagado = (float) ($form['valor_pagado_siniestros'] ?? 0);
        $errors = [];

        if ($totalServicios <= 0) {
            $errors[] = 'total_servicios no puede ser 0.';
        }
        if ($facturacion <= 0) {
            $errors[] = 'facturacion_mensual no puede ser 0.';
        }

        $sumTipos = collect($form['clasificacion_por_tipo'] ?? [])->sum(fn ($row) => (float) ($row['cantidad'] ?? 0));
        if ($totalSiniestros >= 1 && round($sumTipos, 2) !== round($totalSiniestros, 2)) {
            $errors[] = 'La suma por tipo debe ser igual a total_siniestros.';
        }

        $freq = $totalServicios > 0 ? round(($totalSiniestros / $totalServicios) * 100, 2) : 0.0;
        $impacto = $facturacion > 0 ? round(($valorPagado / $facturacion) * 100, 2) : 0.0;
        $cumpleA = $totalServicios > 0 && $freq <= 3;
        $cumpleB = $facturacion > 0 && $impacto <= 1;

        return [
            'numerator' => $totalSiniestros + $valorPagado,
            'denominator' => $totalServicios + $facturacion,
            'result_percentage' => $freq,
            'complies' => $cumpleA && $cumpleB,
            'errors' => $errors,
        ];
    }
}
