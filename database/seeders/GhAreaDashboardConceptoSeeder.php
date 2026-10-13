<?php

namespace Database\Seeders;

use App\Models\GhAreaDashboardConcepto;
use App\Models\GhAreaDashboardConceptoCargo;
use Illuminate\Database\Seeder;

/**
 * Mapa inicial cargo → concepto (tabla operativa GH). Idempotente por name / position_key.
 */
class GhAreaDashboardConceptoSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            'ADMINISTRACION' => [
                'ANALISTA CONTABLE',
                'ANALISTA DE COMPRAS',
                'ANALISTA DE FACTURACION',
                'ANALISTA DE FORMACION Y BIENESTAR',
                'ANALISTA DE NOMINA',
                'ANALISTA DE PROGRAMACION',
                'ANALISTA DE SELECCION',
                'ANALISTA DE TESORERIA',
                'ANALISTA JURIDICO',
                'APRENDIZ GESTION HUMANA',
                'ASISTENTE CONTABLE',
                'ASISTENTE DE CARTERA',
                'ASISTENTE DE ALMACEN',
                'ASISTENTE DE NOMINA',
                'ASISTENTE GESTION DOCUMENTAL',
                'ASISTENTE GESTION HUMANA',
                'COORDINACIÓN ESTRATÉGICA DE UNIONES TEMPORALES',
                'COORDINADOR CONTABLE',
                'COORDINADORA DE SELECCION CONTRATACION Y DESVINCUL',
                'COORDINADORA SST',
                'DIRECCIÓN NACIONAL FINANCIERA Y ADMON',
                'DIRECCION NACIONAL GESTION HUMANA',
                'DIRECTORA DE SERVICIO AL CLIENTE',
                'DIRECTORA JURIDICO',
                'DISENADOR GRAFICO',
                'EJECUTIVO COMERCIAL DE PROYECTOS TECNOLOGICOS',
                'GERENTE',
                'JEFE DE CALIDAD Y SERVICIO AL CLIENTE',
                'JEFE DE PLANEACIÓN Y PROGRAMACIÓN',
                'MENSAJEROS',
            ],
            'COMERCIAL' => [
                'ANALISTA COMERCIAL',
                'ASISTENTE COMERCIAL',
                'COORDINADOR DE PROYECTOS TECNOLOGICOS',
                'COORDINADORA COMERCIAL',
                'DIRECTORA COMERCIAL',
                'EJECUTIVOS COMERCIAL',
                'ESTRATEGA DE MARKETING Y VENTAS',
            ],
            'OPERACIONES' => [
                'ANALISTA DE OPERACIONES',
                'ANALISTA DE PROGRAMACION MENSUAL',
                'COORDINADOR CARTAGENA',
                'COORDINADOR CENTRAL DE MONITOREO',
                'COORDINADOR DE GESTION DEL RIESGO',
                'COORDINADOR DE OPERACIONES',
                'COORDINADORES MENSUAL',
                'DIRECTOR NACIONAL OPERACIONES Y GESTION DE RIESGOS',
                'JEFE DE SEGURIDAD',
                'SUPERVISOR DE INFRAESTRUCTURA IN HOUSE',
                'SUPERVISOR SJ',
                'SUPERVISORES',
            ],
            'TECNOLOGIA' => [
                'ANALISTA TIC Y SISTEMAS',
                'ASISTENTE SOPORTE TECNICO',
                'ASISTENTE TIC',
                'COORDINADOR DE TECNOLOGIA',
            ],
            'ESCOLTAS' => [
                'COORDINADORES',
                'ESCOLTA',
                'ESCOLTA MENSUAL',
            ],
            'GUARDAS' => [
                'GUARDA',
                'GUARDA SJ',
                'GUARDAS MOTORIZADOS',
            ],
            'MONITOREO' => [
                'OPERADOR',
                'OPERADOR SJ',
            ],
        ];

        $sort = 0;
        foreach ($map as $conceptoName => $cargos) {
            $sort++;
            $concepto = GhAreaDashboardConcepto::query()->firstOrCreate(
                ['name' => $conceptoName],
                ['sort_order' => $sort]
            );

            if ((int) $concepto->sort_order !== $sort) {
                $concepto->update(['sort_order' => $sort]);
            }

            foreach ($cargos as $cargo) {
                $key = GhAreaDashboardConceptoCargo::normalizeKey($cargo);
                GhAreaDashboardConceptoCargo::query()->updateOrCreate(
                    ['position_key' => $key],
                    [
                        'concepto_id' => $concepto->id,
                        'position_label' => trim($cargo),
                    ]
                );
            }
        }
    }
}
