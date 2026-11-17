<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * OC autorizadas del solicitante para Mis solicitudes (listado y Excel).
 */
final class MyRequestsOrdersService
{
    /**
     * Suma de OC autorizadas de las solicitudes ya hidratadas con `withSum`.
     *
     * @param  iterable<mixed>  $requests
     */
    public function totalFrom(iterable $requests): float
    {
        $total = 0.0;

        foreach ($requests as $request) {
            $total += (float) ($request->total_oc ?? 0);
        }

        return $total;
    }

    /**
     * @return EloquentCollection<int, PurchaseOrder>
     */
    public function authorizedOrdersForPurchases(int $userId, PurchaseMyRequestsFilterBag $filters): EloquentCollection
    {
        $sourceIds = PurchaseRequest::query()->where('user_id', $userId);
        $filters->apply($sourceIds);

        return $this->authorizedOrders(PurchaseOrderService::SOURCE_PURCHASE, $sourceIds);
    }

    /**
     * @return EloquentCollection<int, PurchaseOrder>
     */
    public function authorizedOrdersForSupplies(int $userId, string $areaKey): EloquentCollection
    {
        $sourceIds = SupplyRequest::query()
            ->where('user_id', $userId)
            ->where('area_key', $areaKey);

        return $this->authorizedOrders(PurchaseOrderService::SOURCE_SUPPLY, $sourceIds);
    }

    /**
     * @param  Builder<PurchaseRequest>|Builder<SupplyRequest>  $sourceIds
     * @return EloquentCollection<int, PurchaseOrder>
     */
    private function authorizedOrders(string $sourceType, Builder $sourceIds): EloquentCollection
    {
        return PurchaseOrder::query()
            ->autorizada()
            ->where('source_type', $sourceType)
            ->whereIn('source_id', $sourceIds->select('id'))
            ->with(['items', 'source'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }
}
