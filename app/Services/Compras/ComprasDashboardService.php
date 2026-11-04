<?php

namespace App\Services\Compras;

use App\Enums\PedidoEtapa;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Services\PurchaseRequests\PurchaseOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ComprasDashboardService
{
    public const TAB_SOLICITUDES = 'solicitudes';

    public const TAB_ORDENES = 'ordenes';

    public function __construct(private ComprasQueueService $queue) {}

    /**
     * @param  array{year: int, month: int|null, area_key: string, tipo: string, tab?: string}  $filters
     * @return array{
     *     filters: array{year: int, month: int|null, area_key: string, tipo: string, tab: string},
     *     referenceDate: Carbon,
     *     areas: array<string, string>,
     *     yearOptions: Collection<int, int>,
     *     yearSelectOptions: array<int, int>,
     *     monthSelectOptions: array<int, string>,
     *     stats: array<string, int>,
     *     orderStats: array<string, mixed>,
     *     chartData: array<string, mixed>,
     *     bandejaLinks: array<string, array<string, string>>,
     *     tabs: list<array{key: string, label: string, url: string, active: bool}>
     * }
     */
    public function build(array $filters): array
    {
        $filters['tab'] = $this->normalizeTab($filters['tab'] ?? self::TAB_SOLICITUDES);
        $referenceMonth = $filters['month'] ?? (int) now()->month;
        $referenceDate = Carbon::create($filters['year'], $referenceMonth, 1)->startOfDay();

        $purchaseRequests = $this->purchaseRequestsQuery($filters)->get();
        $bandejaFilters = ComprasQueueFilterBag::fromDashboardFilters($filters);
        $bandejaStats = $this->queue->stats($bandejaFilters);

        $statsByEstado = $purchaseRequests->groupBy('estado')->map->count();
        $statsByMonth = $purchaseRequests
            ->groupBy(fn (PurchaseRequest $request) => (int) $request->fecha_solicitud?->format('n'))
            ->map->count();

        $statsByArea = $purchaseRequests
            ->groupBy('area_key')
            ->map->count()
            ->sortDesc()
            ->take(5);

        $bandejaByEstado = collect([
            PurchaseRequest::COMPRAS_PENDIENTE => $bandejaStats['pendiente'],
            PurchaseRequest::COMPRAS_EN_CURSO => $bandejaStats['en_curso'],
            PurchaseRequest::COMPRAS_COMPLETADO => $bandejaStats['completado'],
            PurchaseRequest::COMPRAS_RECHAZADO => $bandejaStats['rechazado'],
        ]);

        $tipoCounts = $bandejaStats['by_tipo']->filter(fn (int $count): bool => $count > 0);

        $estadoLabels = PurchaseRequest::estadosComprasLabels();
        // Decisión del director con el nombre de la etapa común.
        $purchaseEstadoLabels = [
            PurchaseRequest::ESTADO_PENDIENTE => PedidoEtapa::PorAutorizar->label(),
            PurchaseRequest::ESTADO_APROBADO => 'Aprobado',
            PurchaseRequest::ESTADO_RECHAZADO => PedidoEtapa::Devuelta->label(),
        ];

        $supplyTrend = SupplyRequest::query()
            ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
            ->when($filters['year'] > 0, fn (Builder $query) => $query->whereYear('created_at', $filters['year']))
            ->whereEtapa([PedidoEtapa::PorComprar, PedidoEtapa::ConOrden, PedidoEtapa::Cerrado])
            ->get()
            ->groupBy(fn (SupplyRequest $request) => (int) $request->created_at?->format('n'))
            ->map->count();

        $monthLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $ordersPayload = $this->buildOrdersPayload($filters, $monthLabels);
        $yearOptions = $this->yearOptions();

        return [
            'filters' => $filters,
            'referenceDate' => $referenceDate,
            'areas' => config('access.areas', []),
            'yearOptions' => $yearOptions,
            'yearSelectOptions' => $yearOptions->mapWithKeys(fn (int $year): array => [$year => $year])->all(),
            'monthSelectOptions' => $this->monthSelectOptions(),
            'stats' => [
                'pendiente_director' => PurchaseRequest::query()
                    ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
                    ->whereEtapa(PedidoEtapa::PorAutorizar)
                    ->count(),
                'bandeja_total' => $bandejaStats['pendiente'] + $bandejaStats['en_curso'],
                'bandeja_pendiente' => $bandejaStats['pendiente'],
                'bandeja_en_curso' => $bandejaStats['en_curso'],
                'completadas_periodo' => $this->completedInPeriodCount($filters),
            ],
            'orderStats' => $ordersPayload['stats'],
            'bandejaLinks' => [
                'index' => ComprasQueueFilterBag::bandejaLinkQuery($filters),
                'pendiente' => ComprasQueueFilterBag::bandejaLinkQuery($filters, PurchaseRequest::COMPRAS_PENDIENTE),
                'en_curso' => ComprasQueueFilterBag::bandejaLinkQuery($filters, PurchaseRequest::COMPRAS_EN_CURSO),
            ],
            'tabs' => $this->tabs($filters),
            'chartData' => [
                'trend' => [
                    'labels' => $monthLabels,
                    'purchase' => collect(range(1, 12))->map(fn (int $month) => $statsByMonth->get($month, 0))->values(),
                    'supply' => collect(range(1, 12))->map(fn (int $month) => $supplyTrend->get($month, 0))->values(),
                ],
                'purchaseStatus' => [
                    'labels' => collect($purchaseEstadoLabels)->values(),
                    'data' => collect($purchaseEstadoLabels)->keys()->map(fn (string $key) => $statsByEstado->get($key, 0))->values(),
                    'colors' => [
                        PedidoEtapa::PorAutorizar->chartColor(),
                        '#15803d',
                        PedidoEtapa::Devuelta->chartColor(),
                    ],
                ],
                'bandejaStatus' => [
                    'labels' => collect($estadoLabels)->values(),
                    'data' => collect($estadoLabels)->keys()->map(fn (string $key) => $bandejaByEstado->get($key, 0))->values(),
                    'colors' => collect($estadoLabels)->keys()
                        ->map(fn (string $key) => PedidoEtapa::fromComprasKey($key)?->chartColor() ?? '#64748b')
                        ->values(),
                ],
                'areas' => [
                    'labels' => $statsByArea->keys()->map(fn (string $key) => config("access.areas.{$key}", $key))->values(),
                    'data' => $statsByArea->values()->values(),
                ],
                'tipoBandeja' => [
                    'labels' => $tipoCounts->keys()->values(),
                    'data' => $tipoCounts->values()->values(),
                ],
                'ordersTrend' => $ordersPayload['charts']['trend'],
                'ordersCycle' => $ordersPayload['charts']['cycle'],
                'ordersEvaluation' => $ordersPayload['charts']['evaluation'],
                'ordersCoverage' => $ordersPayload['charts']['coverage'],
            ],
        ];
    }

    public static function normalizeTab(mixed $tab): string
    {
        return $tab === self::TAB_ORDENES ? self::TAB_ORDENES : self::TAB_SOLICITUDES;
    }

    /**
     * @param  array{year: int, month: int|null, area_key: string, tipo: string, tab: string}  $filters
     * @return list<array{key: string, label: string, url: string, active: bool}>
     */
    private function tabs(array $filters): array
    {
        $query = array_filter([
            'year' => $filters['year'],
            'month' => $filters['month'],
            'area_key' => $filters['area_key'],
            'tipo' => $filters['tipo'],
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            [
                'key' => self::TAB_SOLICITUDES,
                'label' => 'Solicitudes',
                'url' => route('compras.dashboard', $query + ['tab' => self::TAB_SOLICITUDES]),
                'active' => $filters['tab'] === self::TAB_SOLICITUDES,
            ],
            [
                'key' => self::TAB_ORDENES,
                'label' => 'Órdenes',
                'url' => route('compras.dashboard', $query + ['tab' => self::TAB_ORDENES]),
                'active' => $filters['tab'] === self::TAB_ORDENES,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function monthSelectOptions(): array
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
        ];
    }

    /**
     * @param  array{year: int, month: int|null, area_key: string, tipo: string}  $filters
     * @param  list<string>  $monthLabels
     * @return array{stats: array<string, mixed>, charts: array<string, mixed>}
     */
    private function buildOrdersPayload(array $filters, array $monthLabels): array
    {
        $orders = $this->emittedOrdersQuery($filters)->get();
        $aprobadas = $this->approvedCount($filters);
        $realizadas = $orders->count();
        $evaluadas = $orders->filter(fn (PurchaseOrder $order): bool => $order->evaluation?->puntaje_porcentaje !== null);
        $puntajes = $evaluadas
            ->map(fn (PurchaseOrder $order): float => (float) $order->evaluation?->puntaje_porcentaje)
            ->values()
            ->all();

        $cicloSolicitud = [];
        $cicloAprobacion = [];
        $ocByMonth = array_fill(1, 12, 0);
        $cicloSolicitudByMonth = array_fill(1, 12, []);
        $cicloAprobacionByMonth = array_fill(1, 12, []);
        $evalByMonth = array_fill(1, 12, []);

        foreach ($orders as $order) {
            $month = $this->orderMonth($order);
            if ($month !== null) {
                $ocByMonth[$month]++;
            }

            $daysSolicitud = $this->cycleDays($order, 'solicitud');
            $daysAprobacion = $this->cycleDays($order, 'aprobacion');

            if ($daysSolicitud !== null) {
                $cicloSolicitud[] = $daysSolicitud;
                if ($month !== null) {
                    $cicloSolicitudByMonth[$month][] = $daysSolicitud;
                }
            }

            if ($daysAprobacion !== null) {
                $cicloAprobacion[] = $daysAprobacion;
                if ($month !== null) {
                    $cicloAprobacionByMonth[$month][] = $daysAprobacion;
                }
            }

            $puntaje = $order->evaluation?->puntaje_porcentaje;
            if ($puntaje !== null && $month !== null) {
                $evalByMonth[$month][] = (float) $puntaje;
            }
        }

        $promedioSolicitud = $this->average($cicloSolicitud);
        $promedioAprobacion = $this->average($cicloAprobacion);
        $promedioEval = $this->average($puntajes);
        $evaluadasCount = $evaluadas->count();
        $coberturaPct = $realizadas > 0
            ? round(($evaluadasCount / $realizadas) * 100, 1)
            : null;
        $relacionPct = $aprobadas > 0
            ? round(($realizadas / $aprobadas) * 100, 1)
            : null;

        return [
            'stats' => [
                'aprobadas' => $aprobadas,
                'realizadas' => $realizadas,
                'relacion_display' => $aprobadas.' / '.$realizadas,
                'relacion_pct' => $relacionPct,
                'relacion_pct_display' => $this->formatNumber($relacionPct, 1, '%'),
                'ciclo_aprobacion' => $promedioAprobacion,
                'ciclo_aprobacion_display' => $this->formatNumber($promedioAprobacion, 1, ' d'),
                'ciclo_solicitud' => $promedioSolicitud,
                'ciclo_solicitud_display' => $this->formatNumber($promedioSolicitud, 1, ' d'),
                'evaluacion_promedio' => $promedioEval,
                'evaluacion_promedio_display' => $this->formatNumber($promedioEval, 1, '%'),
                'evaluadas' => $evaluadasCount,
                'cobertura_pct' => $coberturaPct,
                'cobertura_display' => $evaluadasCount.' / '.$realizadas,
                'cobertura_pct_display' => $this->formatNumber($coberturaPct, 1, '%'),
            ],
            'charts' => [
                'trend' => [
                    'labels' => $monthLabels,
                    'data' => collect(range(1, 12))->map(fn (int $month) => $ocByMonth[$month])->values(),
                ],
                'cycle' => [
                    'labels' => $monthLabels,
                    'desde_aprobacion' => collect(range(1, 12))
                        ->map(fn (int $month) => $this->average($cicloAprobacionByMonth[$month]) ?? 0)
                        ->values(),
                    'desde_solicitud' => collect(range(1, 12))
                        ->map(fn (int $month) => $this->average($cicloSolicitudByMonth[$month]) ?? 0)
                        ->values(),
                ],
                'evaluation' => [
                    'labels' => $monthLabels,
                    'data' => collect(range(1, 12))
                        ->map(fn (int $month) => $this->average($evalByMonth[$month]) ?? 0)
                        ->values(),
                ],
                'coverage' => [
                    'labels' => ['Evaluadas', 'Sin evaluar'],
                    'data' => [$evaluadasCount, max($realizadas - $evaluadasCount, 0)],
                ],
            ],
        ];
    }

    /**
     * Pedidos ya autorizados: SC aprobado + SU aprobado por Calidad (aunque aún no tengan OC).
     *
     * @param  array{year: int, month: int|null, area_key: string, tipo: string}  $filters
     */
    private function approvedCount(array $filters): int
    {
        $purchases = 0;
        if ($filters['tipo'] !== 'supply') {
            $purchases = PurchaseRequest::query()
                ->where('estado', PurchaseRequest::ESTADO_APROBADO)
                ->whereNotNull('fecha_aprobacion')
                ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
                ->when($filters['year'] > 0, fn (Builder $query) => $query->whereYear('fecha_aprobacion', $filters['year']))
                ->when($filters['month'] !== null, fn (Builder $query) => $query->whereMonth('fecha_aprobacion', $filters['month']))
                ->count();
        }

        $supplies = 0;
        if ($filters['tipo'] !== 'purchase') {
            $supplies = SupplyRequest::query()
                ->whereEtapa([PedidoEtapa::PorComprar, PedidoEtapa::ConOrden, PedidoEtapa::Cerrado])
                ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
                ->when($filters['year'] > 0, fn (Builder $query) => $query->whereYear('created_at', $filters['year']))
                ->when($filters['month'] !== null, fn (Builder $query) => $query->whereMonth('created_at', $filters['month']))
                ->count();
        }

        return $purchases + $supplies;
    }

    /**
     * Órdenes emitidas (no anuladas). `fecha` es varchar Y-m-d: se filtra por rango de texto.
     *
     * @param  array{year: int, month: int|null, area_key: string, tipo: string}  $filters
     */
    private function emittedOrdersQuery(array $filters): Builder
    {
        [$dateFrom, $dateTo] = ComprasQueueFilterBag::dateRangeFromDashboardFilters(
            (int) $filters['year'],
            $filters['month'] ?? null,
        );

        return PurchaseOrder::query()
            ->with(['source', 'evaluation'])
            ->whereIn('estado', [
                PurchaseOrder::ESTADO_EMITIDA,
                PurchaseOrder::ESTADO_ANULACION_PENDIENTE,
            ])
            ->when($dateFrom !== null, fn (Builder $query) => $query->where('fecha', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query) => $query->where('fecha', '<=', $dateTo))
            ->when(
                $filters['tipo'] === 'purchase',
                fn (Builder $query) => $query->where('source_type', PurchaseOrderService::SOURCE_PURCHASE),
            )
            ->when(
                $filters['tipo'] === 'supply',
                fn (Builder $query) => $query->where('source_type', PurchaseOrderService::SOURCE_SUPPLY),
            )
            ->when($filters['area_key'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $outer) use ($filters): void {
                    $outer->where(function (Builder $purchase) use ($filters): void {
                        $purchase->where('source_type', PurchaseOrderService::SOURCE_PURCHASE)
                            ->whereHas('source', fn (Builder $source) => $source->where('area_key', $filters['area_key']));
                    })->orWhere(function (Builder $supply) use ($filters): void {
                        $supply->where('source_type', PurchaseOrderService::SOURCE_SUPPLY)
                            ->whereHas('source', fn (Builder $source) => $source->where('area_key', $filters['area_key']));
                    });
                });
            });
    }

    /**
     * Días transcurridos hasta generar la OC. Cada orden cuenta por separado.
     * Suministros no tienen fecha de autorización: se usa created_at en ambos orígenes.
     */
    private function cycleDays(PurchaseOrder $order, string $origin): ?int
    {
        $ocDate = $this->parseDate($order->fecha);
        if ($ocDate === null) {
            return null;
        }

        $source = $order->source;
        $start = null;

        if ($source instanceof PurchaseRequest) {
            $start = $origin === 'aprobacion'
                ? $source->fecha_aprobacion
                : $source->fecha_solicitud;
        } elseif ($source instanceof SupplyRequest) {
            $start = $source->created_at;
        }

        if ($start === null) {
            return null;
        }

        $startDate = Carbon::parse($start)->startOfDay();
        $days = (int) round($startDate->diffInDays($ocDate->startOfDay(), false));

        return max($days, 0);
    }

    private function orderMonth(PurchaseOrder $order): ?int
    {
        $date = $this->parseDate($order->fecha);

        return $date?->month;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<int|float>|array<int, list<int|float>>  $values
     */
    private function average(array $values): ?float
    {
        if ($values === []) {
            return null;
        }

        return round(array_sum($values) / count($values), 1);
    }

    private function formatNumber(?float $value, int $decimals, string $suffix): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format($value, $decimals, ',', '.').$suffix;
    }

    /**
     * @param  array{year: int, month: int|null, area_key: string, tipo: string}  $filters
     */
    private function purchaseRequestsQuery(array $filters): Builder
    {
        return PurchaseRequest::query()
            ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
            ->when($filters['year'] > 0, fn (Builder $query) => $query->whereYear('fecha_solicitud', $filters['year']))
            ->when($filters['month'] !== null, fn (Builder $query) => $query->whereMonth('fecha_solicitud', $filters['month']))
            ->when($filters['tipo'] === 'supply', fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    /**
     * @param  array{year: int, month: int|null, area_key: string, tipo: string}  $filters
     */
    private function completedInPeriodCount(array $filters): int
    {
        $purchaseCompleted = PurchaseRequest::query()
            ->whereEtapa(PedidoEtapa::Cerrado)
            ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
            ->when($filters['year'] > 0, fn (Builder $query) => $query->whereYear('procesado_compras_at', $filters['year']))
            ->when($filters['month'] !== null, fn (Builder $query) => $query->whereMonth('procesado_compras_at', $filters['month']))
            ->when($filters['tipo'] === 'supply', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->count();

        $supplyCompleted = SupplyRequest::query()
            ->whereEtapa(PedidoEtapa::Cerrado)
            ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
            ->when($filters['year'] > 0, fn (Builder $query) => $query->whereYear('updated_at', $filters['year']))
            ->when($filters['month'] !== null, fn (Builder $query) => $query->whereMonth('updated_at', $filters['month']))
            ->when($filters['tipo'] === 'purchase', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->count();

        return $purchaseCompleted + $supplyCompleted;
    }

    /**
     * @return Collection<int, int>
     */
    private function yearOptions(): Collection
    {
        $minPurchaseYear = PurchaseRequest::query()->min('fecha_solicitud');
        $minSupplyYear = SupplyRequest::query()->min('created_at');
        $minOrderYear = PurchaseOrder::query()->min('fecha');

        $candidates = collect([
            $minPurchaseYear ? (int) Carbon::parse($minPurchaseYear)->year : null,
            $minSupplyYear ? (int) Carbon::parse($minSupplyYear)->year : null,
            $minOrderYear ? (int) Carbon::parse($minOrderYear)->year : null,
            now()->year,
        ])->filter();

        $startYear = (int) $candidates->min();

        return collect(range(now()->year, max($startYear, 2024)));
    }
}
