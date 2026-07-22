<?php

namespace Database\Seeders;

use App\Models\Indicator;
use Illuminate\Database\Seeder;

class IndicadorSeeder extends Seeder
{
    public function run(): void
    {
        $indicators = [
            [
                'code' => 'FT-OP-01',
                'name' => 'Capacitacion',
                'unit' => 'percentage',
                'target_value' => 90,
                'target_operator' => '>=',
                'frequency' => 'monthly',
                'formula_description' => 'personal_capacitado / total_personal * 100',
                'required_fields' => ['total_personal', 'personal_capacitado', 'analisis_texto'],
                'allows_over_100' => false,
            ],
            [
                'code' => 'FT-OP-02',
                'name' => 'Servicios No Conformes',
                'unit' => 'percentage',
                'target_value' => 10,
                'target_operator' => '<=',
                'frequency' => 'monthly',
                'formula_description' => 'no_conformes / total_servicios * 100',
                'required_fields' => ['total_servicios', 'no_conformes', 'analisis_texto'],
                'allows_over_100' => false,
            ],
            [
                'code' => 'FT-OP-03',
                'name' => 'Siniestralidad',
                'unit' => 'percentage',
                'target_value' => 3,
                'target_operator' => '<=',
                'frequency' => 'monthly',
                'formula_description' => 'Cumple cuando frecuencia operativa <= 3 e impacto economico <= 1',
                'required_fields' => [
                    'total_servicios',
                    'total_siniestros',
                    'clasificacion_por_tipo',
                    'facturacion_mensual',
                    'valor_pagado_siniestros',
                    'analisis_texto',
                ],
                'allows_over_100' => false,
            ],
            [
                'code' => 'FT-OP-04',
                'name' => 'Eficacia Supervision',
                'unit' => 'percentage',
                'target_value' => 90,
                'target_operator' => '>=',
                'frequency' => 'monthly',
                'formula_description' => 'supervisiones_realizadas / supervisiones_programadas * 100',
                'required_fields' => ['supervisiones_programadas', 'supervisiones_realizadas', 'analisis_texto'],
                'allows_over_100' => false,
            ],
            [
                'code' => 'FT-OP-05',
                'name' => 'Visita a Clientes',
                'unit' => 'percentage',
                'target_value' => 100,
                'target_operator' => '>=',
                'frequency' => 'monthly',
                'formula_description' => 'visitas_realizadas / visitas_programadas * 100',
                'required_fields' => ['visitas_programadas', 'visitas_realizadas', 'analisis_texto'],
                'allows_over_100' => true,
            ],
            [
                'code' => 'FT-OP-06',
                'name' => 'Estrategias para evitar materializacion',
                'unit' => 'percentage',
                'target_value' => 0,
                'target_operator' => '==',
                'frequency' => 'monthly',
                'formula_description' => 'eventos_indeseables / total_clientes_cadena * 100',
                'required_fields' => ['total_clientes_cadena', 'eventos_indeseables', 'analisis_texto'],
                'allows_over_100' => false,
            ],
            [
                'code' => 'FT-OP-07',
                'name' => 'Analisis de Riesgos',
                'unit' => 'percentage',
                'target_value' => 100,
                'target_operator' => '>=',
                'frequency' => 'monthly',
                'formula_description' => 'analisis_realizados / analisis_programados * 100',
                'required_fields' => ['analisis_programados', 'analisis_realizados', 'analisis_texto'],
                'allows_over_100' => true,
            ],
            [
                'code' => 'FT-OP-08',
                'name' => 'Inventario puestos seguridad fisica',
                'unit' => 'percentage',
                'target_value' => 100,
                'target_operator' => '>=',
                'frequency' => 'quarterly',
                'formula_description' => 'inventarios_realizados / inventarios_programados * 100',
                'required_fields' => ['inventarios_programados', 'inventarios_realizados', 'analisis_texto'],
                'allows_over_100' => false,
            ],
            [
                'code' => 'FT-OP-09',
                'name' => 'Inventario de armas',
                'unit' => 'percentage',
                'target_value' => 100,
                'target_operator' => '>=',
                'frequency' => 'monthly',
                'formula_description' => 'armas_inspeccionadas / armas_programadas * 100',
                'required_fields' => ['armas_programadas', 'armas_inspeccionadas', 'analisis_texto'],
                'allows_over_100' => true,
            ],
        ];

        $defaults = [
            'FT-OP-01' => ['target_value' => 90, 'critical_value' => 80],
            'FT-OP-02' => ['target_value' => 10, 'critical_value' => 15],
            'FT-OP-03' => ['target_value' => 3, 'critical_value' => 1],
            'FT-OP-04' => ['target_value' => 90, 'critical_value' => 80],
            'FT-OP-05' => ['target_value' => 100, 'critical_value' => 90],
            'FT-OP-06' => ['target_value' => 0, 'critical_value' => 0],
            'FT-OP-07' => ['target_value' => 100, 'critical_value' => 90],
            'FT-OP-08' => ['target_value' => 100, 'critical_value' => 90],
            'FT-OP-09' => ['target_value' => 100, 'critical_value' => 90],
        ];

        foreach ($indicators as $indicator) {
            $values = $defaults[$indicator['code']] ?? ['target_value' => $indicator['target_value'], 'critical_value' => $indicator['target_value']];

            Indicator::query()->updateOrCreate(
                ['code' => $indicator['code']],
                $indicator + $values + ['is_active' => true]
            );
        }
    }
}
