<?php

namespace App\Services\Compras;

use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use Illuminate\Support\Collection;

class ComprasQueueService
{
    public const DEFAULT_LIMIT = 200;

    /** @var array<int, string> */
    public const SUPPLY_BANDEJA_STATUSES = [
        'aprobada_calidad',
        'en_compras',
        'completada',
    ];

    /**
     * @return array{items: Collection<int, array<string, mixed>>, truncated: bool, total_matching: int}
     */
    public function resolve(ComprasQueueFilterBag $filters): array
    {
        $purchaseItems = PurchaseRequest::query()
            ->with(['user', 'aprobador', 'items'])
            ->where('estado', PurchaseRequest::ESTADO_APROBADO)
            ->tap(fn ($query) => $filters->applyPurchaseFilters($query))
            ->get()
            ->map(fn (PurchaseRequest $request): array => $this->mapPurchaseItem($request));

        $supplyItems = SupplyRequest::query()
            ->with(['user', 'items.product'])
            ->whereIn('status', self::SUPPLY_BANDEJA_STATUSES)
            ->tap(fn ($query) => $filters->applySupplyFilters($query))
            ->get()
            ->map(fn (SupplyRequest $request): array => $this->mapSupplyItem($request));

        $all = $purchaseItems
            ->concat($supplyItems)
            ->sortByDesc(fn (array $item) => $item['fecha']?->getTimestamp() ?? 0)
            ->values();

        if ($filters->tipo === 'purchase') {
            $all = $all->where('tipo', 'purchase')->values();
        }

        if ($filters->tipo === 'supply') {
            $all = $all->where('tipo', 'supply')->values();
        }

        $totalMatching = $all->count();
        $truncated = false;

        if (! $filters->hasDateRangeFilter() && $totalMatching > self::DEFAULT_LIMIT) {
            $all = $all->take(self::DEFAULT_LIMIT)->values();
            $truncated = true;
        }

        return [
            'items' => $all,
            'truncated' => $truncated,
            'total_matching' => $totalMatching,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(ComprasQueueFilterBag $filters): Collection
    {
        return $this->resolve($filters)['items'];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPurchaseItem(PurchaseRequest $request): array
    {
        $estadoCompras = $request->estado_compras ?? PurchaseRequest::COMPRAS_PENDIENTE;

        return [
            'tipo' => 'purchase',
            'tipo_label' => 'Solicitud compra',
            'id' => $request->id,
            'folio' => $request->folio(),
            'fecha' => $request->fecha_aprobacion ?? $request->created_at,
            'solicitante' => $request->user?->name,
            'area' => $request->areaLabel(),
            'estado' => $estadoCompras,
            'estado_label' => PurchaseRequest::estadosComprasLabels()[$estadoCompras] ?? '—',
            'model' => $request,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSupplyItem(SupplyRequest $request): array
    {
        $estadoCompras = match ($request->status) {
            'en_compras' => PurchaseRequest::COMPRAS_EN_CURSO,
            'completada' => PurchaseRequest::COMPRAS_COMPLETADO,
            default => PurchaseRequest::COMPRAS_PENDIENTE,
        };

        return [
            'tipo' => 'supply',
            'tipo_label' => 'Suministro',
            'id' => $request->id,
            'folio' => $request->folio(),
            'fecha' => $request->updated_at,
            'solicitante' => $request->user?->name,
            'area' => config("access.areas.{$request->area_key}", $request->area_key),
            'estado' => $estadoCompras,
            'estado_label' => PurchaseRequest::estadosComprasLabels()[$estadoCompras] ?? '—',
            'model' => $request,
        ];
    }
}
