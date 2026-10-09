<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * KPIs y series del Dashboard MT-ST-04 (universo default: activos en ficha).
 */
final class MtSt04DashboardService
{
    private const CARGO_CHART_LIMIT = 15;

    public function __construct(
        private readonly MtSt04ListService $listService,
    ) {}

    /**
     * @param  array{
     *     ficha_estado?: string|null,
     *     ciudad?: string|null,
     *     cargo?: string|null,
     *     puesto?: string|null,
     * }  $filters
     * @return array{
     *     kpis: array{
     *         examen1: array{total: int, vigente: int, vencera: int, vencido: int},
     *         examen2: array{total: int, vigente: int, vencera: int, vencido: int, no_aplica: int},
     *         apto: array{si: int, no: int, sin_dato: int}
     *     },
     *     charts: array{
     *         examen1_estados: array{labels: list<string>, data: list<int>},
     *         examen2_estados: array{labels: list<string>, data: list<int>},
     *         aptos: array{labels: list<string>, data: list<int>},
     *         examen1_vencimientos_anio: array{labels: list<string>, data: list<int>},
     *         examen2_vencimientos_anio: array{labels: list<string>, data: list<int>},
     *         examen1_por_cargo: array{labels: list<string>, data: list<int>},
     *         examen2_por_cargo: array{labels: list<string>, data: list<int>}
     *     },
     *     filters: array{ficha_estado: string, ciudad: string, cargo: string, puesto: string},
     *     labels: array<string, string>
     * }
     */
    public function metrics(array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);
        $base = $this->listService->filteredQuery([
            'ficha_estado' => $normalized['ficha_estado'],
            'ciudad' => $normalized['ciudad'],
            'cargo' => $normalized['cargo'],
            'puesto' => $normalized['puesto'],
        ], ordered: false);

        $examen1 = $this->countEstadosExamen1($base);
        $examen2 = $this->countEstadosExamen2($base);
        $apto = $this->countAptos($base);

        $baseExamen2 = $this->querySinNoAplicaExamen2($base);

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
                'examen1_vencimientos_anio' => $this->countByVencimientoYear(
                    $base,
                    'fecha_vencimiento_1',
                ),
                'examen2_vencimientos_anio' => $this->countByVencimientoYear(
                    $baseExamen2,
                    'fecha_vencimiento_2',
                ),
                'examen1_por_cargo' => $this->countByCargo($base),
                'examen2_por_cargo' => $this->countByCargo($baseExamen2),
            ],
            'filters' => $normalized,
            'labels' => [
                'examen1_total' => 'Total psicofísicos',
                'examen1_vigente' => 'Vigente',
                'examen1_vencera' => 'Vencerá',
                'examen1_vencido' => 'Vencido',
                'examen2_total' => 'Total psicosensométricos',
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
     * @param  array{
     *     ficha_estado?: string|null,
     *     ciudad?: string|null,
     *     cargo?: string|null,
     *     puesto?: string|null,
     * }  $filters
     * @return array{ficha_estado: string, ciudad: string, cargo: string, puesto: string}
     */
    public function normalizeFilters(array $filters): array
    {
        $fichaEstado = (string) ($filters['ficha_estado'] ?? EmployeeFichaProfile::STATUS_ACTIVO);

        if (! in_array($fichaEstado, [
            EmployeeFichaProfile::STATUS_ACTIVO,
            EmployeeFichaProfile::STATUS_DESVINCULADO,
            'sin_ficha',
            'todos',
        ], true)) {
            $fichaEstado = EmployeeFichaProfile::STATUS_ACTIVO;
        }

        return [
            'ficha_estado' => $fichaEstado,
            'ciudad' => $this->normalizeSelectFilter($filters['ciudad'] ?? null),
            'cargo' => $this->normalizeSelectFilter($filters['cargo'] ?? null),
            'puesto' => $this->normalizeSelectFilter($filters['puesto'] ?? null),
        ];
    }

    private function normalizeSelectFilter(mixed $value): string
    {
        $normalized = trim((string) ($value ?? 'todos'));

        return $normalized === '' ? 'todos' : $normalized;
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

        $sinNoAplica = $this->querySinNoAplicaExamen2($base);

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
     * @return Builder<MtSt04Registro>
     */
    private function querySinNoAplicaExamen2(Builder $base): Builder
    {
        return (clone $base)
            ->where(function (Builder $query): void {
                $query->whereNull('mt_st_04_registros.estado_2')
                    ->orWhere('mt_st_04_registros.estado_2', '!=', MtSt04Registro::ESTADO_NO_APLICA);
            });
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

    /**
     * Tendencia: cantidad de vencimientos por año calendario.
     *
     * @param  Builder<MtSt04Registro>  $base
     * @param  'fecha_vencimiento_1'|'fecha_vencimiento_2'  $column
     * @return array{labels: list<string>, data: list<int>}
     */
    private function countByVencimientoYear(Builder $base, string $column): array
    {
        if (! in_array($column, ['fecha_vencimiento_1', 'fecha_vencimiento_2'], true)) {
            return ['labels' => [], 'data' => []];
        }

        $qualified = "mt_st_04_registros.{$column}";
        $driver = Schema::getConnection()->getDriverName();
        $yearExpr = $driver === 'sqlite'
            ? "CAST(strftime('%Y', {$qualified}) AS INTEGER)"
            : "YEAR({$qualified})";

        $rows = $this->aggregateQuery($base)
            ->whereNotNull($qualified)
            ->selectRaw("{$yearExpr} as anio, COUNT(*) as aggregate")
            ->groupByRaw($yearExpr)
            ->orderBy('anio')
            ->get();

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $year = (int) $row->anio;
            if ($year < 2000 || $year > 2100) {
                continue;
            }
            $labels[] = (string) $year;
            $data[] = (int) $row->aggregate;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Barras horizontales: cantidad por cargo (Ficha); top N + Otros.
     *
     * @param  Builder<MtSt04Registro>  $base
     * @return array{labels: list<string>, data: list<int>}
     */
    private function countByCargo(Builder $base): array
    {
        $cargoExpr = "COALESCE(NULLIF(TRIM(ficha.position_name), ''), 'Sin cargo')";

        $rows = $this->aggregateQuery($base)
            ->selectRaw("{$cargoExpr} as cargo, COUNT(*) as aggregate")
            ->groupByRaw($cargoExpr)
            ->orderByDesc('aggregate')
            ->orderBy('cargo')
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => [], 'data' => []];
        }

        $top = $rows->take(self::CARGO_CHART_LIMIT);
        $rest = $rows->slice(self::CARGO_CHART_LIMIT);
        $otros = (int) $rest->sum(fn ($row): int => (int) $row->aggregate);

        $labels = $top->map(fn ($row): string => (string) $row->cargo)->values()->all();
        $data = $top->map(fn ($row): int => (int) $row->aggregate)->values()->all();

        if ($otros > 0) {
            $labels[] = 'Otros';
            $data[] = $otros;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Limpia SELECT/ORDER del join de ListService para agregaciones GROUP BY.
     *
     * @param  Builder<MtSt04Registro>  $base
     * @return Builder<MtSt04Registro>
     */
    private function aggregateQuery(Builder $base): Builder
    {
        $query = clone $base;
        $query->getQuery()->columns = null;
        $query->getQuery()->orders = null;
        $query->getQuery()->groups = null;

        return $query;
    }
}
