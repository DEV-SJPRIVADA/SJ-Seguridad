<?php

namespace App\Services\Compras;

use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use Illuminate\Support\Collection;

class ComprasQueueService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(ComprasQueueFilterBag $filters): Collection
    {
        $purchaseItems = PurchaseRequest::query()
            ->with(['user', 'aprobador', 'items'])
            ->where('estado', PurchaseRequest::ESTADO_APROBADO)
            ->tap(fn ($query) => $filters->applyPurchaseFilters($query))
            ->latest('fecha_aprobacion')
            ->get()
            ->map(fn (PurchaseRequest $request): array => [
                'tipo' => 'purchase',
                'tipo_label' => 'Solicitud compra',
                'id' => $request->id,
                'folio' => $request->folio(),
                'fecha' => $request->fecha_aprobacion ?? $request->created_at,
                'solicitante' => $request->user?->name,
                'area' => $request->areaLabel(),
                'estado' => $request->estado_compras,
                'estado_label' => $request->estadoComprasLabel(),
                'model' => $request,
            ]);

        $supplyItems = SupplyRequest::query()
            ->with(['user', 'items.product'])
            ->whereIn('status', ['aprobada_calidad', 'en_compras'])
            ->tap(fn ($query) => $filters->applySupplyFilters($query))
            ->latest('updated_at')
            ->get()
            ->map(fn (SupplyRequest $request): array => [
                'tipo' => 'supply',
                'tipo_label' => 'Suministro',
                'id' => $request->id,
                'folio' => $request->folio(),
                'fecha' => $request->updated_at,
                'solicitante' => $request->user?->name,
                'area' => config("access.areas.{$request->area_key}", $request->area_key),
                'estado' => $request->status === 'en_compras' ? PurchaseRequest::COMPRAS_EN_CURSO : PurchaseRequest::COMPRAS_PENDIENTE,
                'estado_label' => $request->statusLabel(),
                'model' => $request,
            ]);

        $all = $purchaseItems->concat($supplyItems)->sortByDesc('fecha')->values();

        if ($filters->tipo === 'purchase') {
            return $all->where('tipo', 'purchase')->values();
        }

        if ($filters->tipo === 'supply') {
            return $all->where('tipo', 'supply')->values();
        }

        return $all;
    }
}
