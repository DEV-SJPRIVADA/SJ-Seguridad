<?php

namespace App\Services\Compras;

use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ComprasDashboardService
{
    public function __construct(private ComprasQueueService $queue) {}

    /**
     * @param  array{year: int, month: int|null, area_key: string, tipo: string}  $filters
     * @return array{
     *     filters: array{year: int, month: int|null, area_key: string, tipo: string},
     *     referenceDate: Carbon,
     *     areas: array<string, string>,
     *     yearOptions: Collection<int, int>,
     *     stats: array<string, int>,
     *     chartData: array<string, mixed>,
     *     bandejaLinks: array<string, array<string, string>>
     * }
     */
    public function build(array $filters): array
    {
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
        $purchaseEstadoLabels = [
            PurchaseRequest::ESTADO_PENDIENTE => 'Pendiente director',
            PurchaseRequest::ESTADO_APROBADO => 'Aprobado',
            PurchaseRequest::ESTADO_RECHAZADO => 'Rechazado',
        ];

        $supplyTrend = SupplyRequest::query()
            ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
            ->when($filters['year'] > 0, fn (Builder $query) => $query->whereYear('created_at', $filters['year']))
            ->whereIn('status', ['aprobada_calidad', 'en_compras', 'completada'])
            ->get()
            ->groupBy(fn (SupplyRequest $request) => (int) $request->created_at?->format('n'))
            ->map->count();

        $monthLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return [
            'filters' => $filters,
            'referenceDate' => $referenceDate,
            'areas' => config('access.areas', []),
            'yearOptions' => $this->yearOptions(),
            'stats' => [
                'pendiente_director' => PurchaseRequest::query()
                    ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
                    ->where('estado', PurchaseRequest::ESTADO_PENDIENTE)
                    ->count(),
                'bandeja_total' => $bandejaStats['total'],
                'bandeja_pendiente' => $bandejaStats['pendiente'],
                'bandeja_en_curso' => $bandejaStats['en_curso'],
                'completadas_periodo' => $this->completedInPeriodCount($filters),
            ],
            'bandejaLinks' => [
                'index' => ComprasQueueFilterBag::bandejaLinkQuery($filters),
                'pendiente' => ComprasQueueFilterBag::bandejaLinkQuery($filters, PurchaseRequest::COMPRAS_PENDIENTE),
                'en_curso' => ComprasQueueFilterBag::bandejaLinkQuery($filters, PurchaseRequest::COMPRAS_EN_CURSO),
            ],
            'chartData' => [
                'trend' => [
                    'labels' => $monthLabels,
                    'purchase' => collect(range(1, 12))->map(fn (int $month) => $statsByMonth->get($month, 0))->values(),
                    'supply' => collect(range(1, 12))->map(fn (int $month) => $supplyTrend->get($month, 0))->values(),
                ],
                'purchaseStatus' => [
                    'labels' => collect($purchaseEstadoLabels)->values(),
                    'data' => collect($purchaseEstadoLabels)->keys()->map(fn (string $key) => $statsByEstado->get($key, 0))->values(),
                ],
                'bandejaStatus' => [
                    'labels' => collect($estadoLabels)->values(),
                    'data' => collect($estadoLabels)->keys()->map(fn (string $key) => $bandejaByEstado->get($key, 0))->values(),
                ],
                'areas' => [
                    'labels' => $statsByArea->keys()->map(fn (string $key) => config("access.areas.{$key}", $key))->values(),
                    'data' => $statsByArea->values()->values(),
                ],
                'tipoBandeja' => [
                    'labels' => $tipoCounts->keys()->values(),
                    'data' => $tipoCounts->values()->values(),
                ],
            ],
        ];
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
            ->where('estado', PurchaseRequest::ESTADO_APROBADO)
            ->where('estado_compras', PurchaseRequest::COMPRAS_COMPLETADO)
            ->when($filters['area_key'] !== '', fn (Builder $query) => $query->where('area_key', $filters['area_key']))
            ->when($filters['year'] > 0, fn (Builder $query) => $query->whereYear('procesado_compras_at', $filters['year']))
            ->when($filters['month'] !== null, fn (Builder $query) => $query->whereMonth('procesado_compras_at', $filters['month']))
            ->when($filters['tipo'] === 'supply', fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->count();

        $supplyCompleted = SupplyRequest::query()
            ->where('status', 'completada')
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

        $candidates = collect([
            $minPurchaseYear ? (int) Carbon::parse($minPurchaseYear)->year : null,
            $minSupplyYear ? (int) Carbon::parse($minSupplyYear)->year : null,
            now()->year,
        ])->filter();

        $startYear = (int) $candidates->min();

        return collect(range(now()->year, max($startYear, 2024)));
    }
}
