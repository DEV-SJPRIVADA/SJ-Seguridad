<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseQuotation;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\PurchaseQuotationBatch;
use Illuminate\Support\Collection;

/**
 * Compone los lotes de cotizaciones a partir de las cotizaciones que comparten `lote`.
 */
class PurchaseQuotationBatchService
{
    /**
     * @return Collection<int, PurchaseQuotationBatch>
     */
    public function forRequest(PurchaseRequest $purchaseRequest): Collection
    {
        $purchaseRequest->loadMissing([
            'quotations.aprobador',
            'quotations.solicitadaPor',
            'quotations.uploadedBy',
            'quotations.purchaseOrder',
        ]);

        return $this->group($purchaseRequest->quotations);
    }

    /**
     * Lotes enviados a un aprobador concreto, incluidos los ya resueltos.
     *
     * @return Collection<int, PurchaseQuotationBatch>
     */
    public function forApprover(User $user): Collection
    {
        $quotations = PurchaseQuotation::query()
            ->with(['purchaseRequest.user', 'aprobador', 'solicitadaPor', 'uploadedBy', 'purchaseOrder'])
            ->where('aprobador_id', $user->getKey())
            ->whereNotNull('solicitada_at')
            ->orderByDesc('solicitada_at')
            ->orderByDesc('id')
            ->get();

        return $this->group($quotations);
    }

    public function pendingCountForApprover(User $user): int
    {
        return PurchaseQuotation::query()
            ->where('aprobador_id', $user->getKey())
            ->where('estado', PurchaseQuotation::ESTADO_PENDIENTE)
            ->whereNotNull('lote')
            ->distinct()
            ->count('lote');
    }

    /**
     * @param  Collection<int, PurchaseQuotation>  $quotations
     * @return Collection<int, PurchaseQuotationBatch>
     */
    public function group(Collection $quotations): Collection
    {
        return $quotations
            ->groupBy(fn (PurchaseQuotation $quotation): string => (string) ($quotation->lote ?: 'legacy-'.$quotation->getKey()))
            ->map(function (Collection $group, string $key): PurchaseQuotationBatch {
                $ordered = $group
                    ->sortBy(fn (PurchaseQuotation $quotation): string => sprintf('%05d-%010d', (int) $quotation->lote_orden, (int) $quotation->getKey()))
                    ->values();

                return new PurchaseQuotationBatch(
                    key: $key,
                    label: (string) ($ordered->first()?->loteLabel() ?? 'LOTE SIN REFERENCIA'),
                    quotations: $ordered,
                );
            })
            ->sortBy(fn (PurchaseQuotationBatch $batch): int => (int) ($batch->first()?->getKey() ?? 0))
            ->values();
    }
}
