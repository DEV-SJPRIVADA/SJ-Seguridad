<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use Illuminate\Database\Eloquent\Builder;

/**
 * KPIs y series del Dashboard MT-ST-04 (universo default: activos en ficha).
 */
final class MtSt04DashboardService
{
    public function __construct(
        private readonly MtSt04ListService $listService,
    ) {}

    /**
     * @param  array{ficha_estado?: string|null}  $filters
     * @return array{
     *     kpis: array{
     *         examen1: array{total: int, vigente: int, vencera: int, vencido: int},
     *         examen2: array{total: int, vigente: int, vencera: int, vencido: int, no_aplica: int},
     *         apto: array{si: int, no: int, sin_dato: int}
     *     },
     *     charts: array{
     *         examen1_estados: array{labels: list<string>, data: list<int>},
     *         examen2_estados: array{labels: list<string>, data: list<int>},
     *         aptos: array{labels: list<string>, data: list<int>}
     *     },
     *     filters: array{ficha_estado: string},
     *     labels: array<string, string>
     * }
     */
    public function metrics(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $base = $this->listService->filteredQuery([
            'ficha_estado' => $normalized['ficha_estado'],
        ], ordered: false);

        $examen1 = $this->countEstadosExamen1($base);
        $examen2 = $this->countEstadosExamen2($base);
        $apto = $this->countAptos($base);

        return [
            'kpis' => [
                'examen1' => [
                    'total' => $examen1['total'],
                    'vigente' => $examen1['vigente'],
                    'vencera' => $examen1['vencera'],
                    'vencido' => $examen1['vencido'],
                ],
                'examen2' => [
                    // Total2 = filas cuyo ESTADO2 no es NO APLICA (incluye vacío/null).
                    'total' => $examen2['total'],
                    'vigente' => $examen2['vigente'],
                    'vencera' => $examen2['vencera'],
                    'vencido' => $examen2['vencido'],
                    'no_aplica' => $examen2['no_aplica'],
                ],
                'apto' => $apto,
            ],
            'charts' => [
                'examen1_estados' => [
                    'labels' => ['VIGENTE', 'VENCERA', 'VENCIDO'],
                    'data' => [
                        $examen1['vigente'],
                        $examen1['vencera'],
                        $examen1['vencido'],
                    ],
                ],
                // Distribución examen 2 sin NO APLICA (segmento aparte vía KPI no_aplica).
                'examen2_estados' => [
                    'labels' => ['VIGENTE', 'VENCERA', 'VENCIDO'],
                    'data' => [
                        $examen2['vigente'],
                        $examen2['vencera'],
                        $examen2['vencido'],
                    ],
                ],
                'aptos' => [
                    'labels' => ['SI', 'NO', 'Sin dato'],
                    'data' => [
                        $apto['si'],
                        $apto['no'],
                        $apto['sin_dato'],
                    ],
                ],
            ],
            'filters' => $normalized,
            'labels' => [
                'examen1_total' => 'Total examen 1',
                'examen1_vigente' => 'Vigente',
                'examen1_vencera' => 'Vencerá',
                'examen1_vencido' => 'Vencido',
                'examen2_total' => 'Total examen 2',
                'examen2_vigente' => 'Vigente',
                'examen2_vencera' => 'Vencerá',
                'examen2_vencido' => 'Vencido',
                'examen2_no_aplica' => 'NO APLICA',
                'apto_si' => 'Aptos',
                'apto_no' => 'No aptos',
            ],
        ];
    }

    /**
     * @param  array{ficha_estado?: string|null}  $filters
     * @return array{ficha_estado: string}
     */
    public function normalizeFilters(array $filters): array
    {
        $fichaEstado = (string) ($filters['ficha_estado'] ?? EmployeeFichaProfile::STATUS_ACTIVO);

        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
            'todos',
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        return [
            'ficha_estado' => $fichaEstado,
        ];
    }

    /**
     * Conteos por estado vía where+count (evita ONLY_FULL_GROUP_BY con el select join de ListService).
     *
     * @param  Builder<MtSt04Registro>  $base
     * @return array{total: int, vigente: int, vencera: int, vencido: int}
     */
    private function countEstadosExamen1(Builder $base): array
    {
        return [
            'total' => (clone $base)->count(),
            'vigente' => (clone $base)
                ->where('mt_st_04_registros.estado_1', MtSt04Registro::ESTADO_VIGENTE)
                ->count(),
            'vencera' => (clone $base)
                ->where('mt_st_04_registros.estado_1', MtSt04Registro::ESTADO_VENCERA)
                ->count(),
            'vencido' => (clone $base)
                ->where('mt_st_04_registros.estado_1', MtSt04Registro::ESTADO_VENCIDO)
                ->count(),
        ];
    }

    /**
     * Examen 2: conteos de vigencia excluyendo NO APLICA; Total2 = no NO APLICA.
     *
     * @param  Builder<MtSt04Registro>  $base
     * @return array{total: int, vigente: int, vencera: int, vencido: int, no_aplica: int}
     */
    private function countEstadosExamen2(Builder $base): array
    {
        $noAplica = (clone $base)
            ->where('mt_st_04_registros.estado_2', MtSt04Registro::ESTADO_NO_APLICA)
            ->count();

        $sinNoAplica = (clone $base)
            ->where(function (Builder $query): void {
                $query->whereNull('mt_st_04_registros.estado_2')
                    ->orWhere('mt_st_04_registros.estado_2', '!=', MtSt04Registro::ESTADO_NO_APLICA);
            });

        return [
            'total' => (clone $sinNoAplica)->count(),
            'vigente' => (clone $sinNoAplica)
                ->where('mt_st_04_registros.estado_2', MtSt04Registro::ESTADO_VIGENTE)
                ->count(),
            'vencera' => (clone $sinNoAplica)
                ->where('mt_st_04_registros.estado_2', MtSt04Registro::ESTADO_VENCERA)
                ->count(),
            'vencido' => (clone $sinNoAplica)
                ->where('mt_st_04_registros.estado_2', MtSt04Registro::ESTADO_VENCIDO)
                ->count(),
            'no_aplica' => $noAplica,
        ];
    }

    /**
     * @param  Builder<MtSt04Registro>  $base
     * @return array{si: int, no: int, sin_dato: int}
     */
    private function countAptos(Builder $base): array
    {
        $si = (clone $base)
            ->where('mt_st_04_registros.apto', MtSt04Registro::APTO_SI)
            ->count();
        $no = (clone $base)
            ->where('mt_st_04_registros.apto', MtSt04Registro::APTO_NO)
            ->count();
        $sinDato = (clone $base)
            ->where(function (Builder $query): void {
                $query->whereNull('mt_st_04_registros.apto')
                    ->orWhere('mt_st_04_registros.apto', '');
            })
            ->count();

        return [
            'si' => $si,
            'no' => $no,
            'sin_dato' => $sinDato,
        ];
    }
}
