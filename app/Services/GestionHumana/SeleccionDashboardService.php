<?php

namespace App\Services\GestionHumana;

use App\Models\SeleccionExamenOcupacional;
use App\Models\SeleccionIngreso;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeleccionDashboardService
{
    public function __construct(
        private readonly SeleccionIngresoService $ingresoService,
        private readonly SeleccionExamenService $examenService,
    ) {}

    /**
     * @param  array{
     *     date_from?: string,
     *     date_to?: string,
     *     commercial_client_id?: string,
     *     responsable_user_id?: string,
     * }  $filters
     * @return array{
     *     kpis: array<string, int>,
     *     charts: array<string, mixed>,
     *     filters: array<string, string>
     * }
     */
    public function metrics(array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);

        /** @var Collection<int, SeleccionIngreso> $ingresos */
        $ingresos = $this->ingresoService
            ->filteredQuery($normalized, ordered: false)
            ->with(['commercialClient:id,name', 'responsable:id,name'])
            ->get([
                'id',
                'fecha_ingreso',
                'commercial_client_id',
                'responsable_user_id',
            ]);

        /** @var Collection<int, SeleccionExamenOcupacional> $examenes */
        $examenes = $this->examenService
            ->filteredQuery($normalized, ordered: false)
            ->with(['commercialClient:id,name', 'responsable:id,name'])
            ->get([
                'id',
                'fecha_arl',
                'solicitud_status_code',
                'solicitud_status_name',
                'commercial_client_id',
                'responsable_user_id',
            ]);

        $enProcesoCode = (string) config('seleccion.solicitud_en_proceso_code', 'EN_PROCESO');
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $ingresosDelMes = $ingresos->filter(function (SeleccionIngreso $row) use ($monthStart, $monthEnd): bool {
            if (! $row->fecha_ingreso instanceof Carbon) {
                return false;
            }

            $date = $row->fecha_ingreso->toDateString();

            return $date >= $monthStart && $date <= $monthEnd;
        })->count();

        $examenesEnProceso = $examenes
            ->where('solicitud_status_code', $enProcesoCode)
            ->count();

        $bySolicitud = $examenes
            ->groupBy(function (SeleccionExamenOcupacional $row): string {
                $name = trim((string) ($row->solicitud_status_name ?: ''));

                return $name !== '' ? $name : (string) ($row->solicitud_status_code ?: 'Sin estado');
            })
            ->map->count()
            ->sortDesc();

        $byClient = $this->distributionByRelation(
            $ingresos,
            $examenes,
            fn (SeleccionIngreso|SeleccionExamenOcupacional $row): string => $row->commercialClient?->name ?: 'Sin cliente',
        );

        $byResponsable = $this->distributionByRelation(
            $ingresos,
            $examenes,
            fn (SeleccionIngreso|SeleccionExamenOcupacional $row): string => $row->responsable?->name ?: 'Sin responsable',
        );

        return [
            'kpis' => [
                'total_ingresos' => $ingresos->count(),
                'total_examenes' => $examenes->count(),
                'ingresos_del_mes' => $ingresosDelMes,
                'examenes_en_proceso' => $examenesEnProceso,
            ],
            'charts' => [
                'by_solicitud_status' => [
                    'labels' => $bySolicitud->keys()->values()->all(),
                    'data' => $bySolicitud->values()->map(fn ($v) => (int) $v)->all(),
                ],
                'ingresos_trend' => $this->ingresosTrend($ingresos, $normalized),
                'by_client' => [
                    'labels' => $byClient->keys()->values()->all(),
                    'data' => $byClient->values()->map(fn ($v) => (int) $v)->all(),
                ],
                'by_responsable' => [
                    'labels' => $byResponsable->keys()->values()->all(),
                    'data' => $byResponsable->values()->map(fn ($v) => (int) $v)->all(),
                ],
            ],
            'filters' => $normalized,
        ];
    }

    /**
     * @param  array{
     *     date_from?: string,
     *     date_to?: string,
     *     commercial_client_id?: string,
     *     responsable_user_id?: string,
     * }  $filters
     * @return array{
     *     date_from: string,
     *     date_to: string,
     *     commercial_client_id: string,
     *     responsable_user_id: string,
     * }
     */
    public function normalizeFilters(array $filters): array
    {
        return [
            'date_from' => trim((string) ($filters['date_from'] ?? '')),
            'date_to' => trim((string) ($filters['date_to'] ?? '')),
            'commercial_client_id' => trim((string) ($filters['commercial_client_id'] ?? '')),
            'responsable_user_id' => trim((string) ($filters['responsable_user_id'] ?? '')),
        ];
    }

    /**
     * @param  Collection<int, SeleccionIngreso>  $ingresos
     * @param  Collection<int, SeleccionExamenOcupacional>  $examenes
     * @param  callable(SeleccionIngreso|SeleccionExamenOcupacional): string  $labelFn
     * @return Collection<string, int>
     */
    private function distributionByRelation(Collection $ingresos, Collection $examenes, callable $labelFn): Collection
    {
        $counts = [];

        foreach ($ingresos as $row) {
            $label = $labelFn($row);
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        foreach ($examenes as $row) {
            $label = $labelFn($row);
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }

        return collect($counts)
            ->sortDesc()
            ->take(12);
    }

    /**
     * @param  Collection<int, SeleccionIngreso>  $ingresos
     * @param  array{date_from: string, date_to: string}  $filters
     * @return array{labels: list<string>, data: list<int>}
     */
    private function ingresosTrend(Collection $ingresos, array $filters): array
    {
        $monthLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $from = $filters['date_from'] !== ''
            ? Carbon::parse($filters['date_from'])->startOfMonth()
            : now()->startOfYear()->startOfMonth();
        $to = $filters['date_to'] !== ''
            ? Carbon::parse($filters['date_to'])->startOfMonth()
            : now()->endOfYear()->startOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        // Cap to 24 months to keep the chart readable.
        if ($from->diffInMonths($to) > 23) {
            $to = $from->copy()->addMonths(23);
        }

        $labels = [];
        $bucketKeys = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $key = $cursor->format('Y-m');
            $bucketKeys[] = $key;
            $labels[] = $monthLabels[(int) $cursor->format('n') - 1].' '.$cursor->format('Y');
            $cursor->addMonth();
        }

        $counts = array_fill_keys($bucketKeys, 0);

        foreach ($ingresos as $row) {
            if (! $row->fecha_ingreso instanceof Carbon) {
                continue;
            }

            $key = $row->fecha_ingreso->format('Y-m');
            if (array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
        }

        return [
            'labels' => $labels,
            'data' => array_map(fn (string $key): int => (int) $counts[$key], $bucketKeys),
        ];
    }
}
