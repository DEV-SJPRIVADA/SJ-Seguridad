<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Totales de órdenes de compra autorizadas (no anuladas) para Mis solicitudes.
 */
trait AggregatesAuthorizedPurchaseOrders
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithAuthorizedOrderTotals(Builder $query): Builder
    {
        return $query
            ->withCount([
                'purchaseOrders as oc_autorizadas_count' => fn (Builder $orders) => $orders->autorizada(),
            ])
            ->withSum([
                'purchaseOrders as total_oc' => fn (Builder $orders) => $orders->autorizada(),
            ], 'total');
    }

    public function totalOcAutorizadas(): float
    {
        return (float) ($this->total_oc ?? 0);
    }

    public function ocAutorizadasCount(): int
    {
        return (int) ($this->oc_autorizadas_count ?? 0);
    }
}
