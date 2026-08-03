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
    public function items(?string $tipo = null, ?string $estadoCompras = null): Collection
    {
        $purchaseItems = PurchaseRequest::query()
            ->with(['user', 'aprobador', 'items'])
            ->where('estado', PurchaseRequest::ESTADO_APROBADO)
            ->when($estadoCompras, fn ($query) => $query->where('estado_compras', $estadoCompras))
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
            ->when($estadoCompras === PurchaseRequest::COMPRAS_PENDIENTE, fn ($query) => $query->where('status', 'aprobada_calidad'))
            ->when($estadoCompras === PurchaseRequest::COMPRAS_EN_CURSO, fn ($query) => $query->where('status', 'en_compras'))
            ->when(in_array($estadoCompras, [PurchaseRequest::COMPRAS_COMPLETADO, PurchaseRequest::COMPRAS_RECHAZADO], true), fn ($query) => $query->whereRaw('1 = 0'))
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

        if ($tipo === 'purchase') {
            return $all->where('tipo', 'purchase')->values();
        }

        if ($tipo === 'supply') {
            return $all->where('tipo', 'supply')->values();
        }

        return $all;
    }
}
