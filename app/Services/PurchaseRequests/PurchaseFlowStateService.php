<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestComment;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestComment;
use App\Models\User;
use App\Support\UppercaseFormText;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Máquina única del pedido: Por comprar, Con orden y Cerrado.
 * No renombra valores guardados y no degrada un pedido ya cerrado.
 */
class PurchaseFlowStateService
{
    public function __construct(private readonly PurchaseOrderService $orders) {}

    public function sync(PurchaseRequest|SupplyRequest $source, ?User $actor = null): void
    {
        if ($source instanceof PurchaseRequest) {
            $this->syncPurchase($source, $actor);

            return;
        }

        $this->syncSupply($source, $actor);
    }

    public function closeBalance(PurchaseRequest|SupplyRequest $source, User $actor, string $motivo): void
    {
        $motivo = trim(UppercaseFormText::uppercaseString($motivo));

        if ($motivo === '') {
            throw new InvalidArgumentException('Indique el motivo para cerrar el saldo.');
        }

        if (! $source->puedeCerrarSaldo()) {
            throw new InvalidArgumentException('Solo se puede cerrar el saldo de un pedido en Por comprar o Con orden.');
        }

        DB::transaction(function () use ($source, $actor, $motivo): void {
            $this->appendMotivo($source, $actor, $motivo);

            if ($source instanceof PurchaseRequest) {
                $source->update([
                    'estado_compras' => PurchaseRequest::COMPRAS_COMPLETADO,
                    'procesado_compras_at' => now(),
                    'procesado_compras_por' => $actor->id,
                ]);

                return;
            }

            $source->update([
                'status' => 'completada',
                'purchasing_manager_id' => $actor->id,
            ]);
        });
    }

    private function syncPurchase(PurchaseRequest $source, ?User $actor): void
    {
        if ($source->estado_compras === PurchaseRequest::COMPRAS_RECHAZADO) {
            return;
        }

        if ($source->estado !== PurchaseRequest::ESTADO_APROBADO) {
            return;
        }

        if ($source->estado_compras === PurchaseRequest::COMPRAS_COMPLETADO) {
            return;
        }

        $next = $this->nextComprasState(PurchaseOrderService::SOURCE_PURCHASE, $source);

        if ($next === $source->estado_compras) {
            return;
        }

        $data = ['estado_compras' => $next];

        if ($next === PurchaseRequest::COMPRAS_COMPLETADO) {
            $data['procesado_compras_at'] = $source->procesado_compras_at ?? now();

            if ($actor !== null) {
                $data['procesado_compras_por'] = $actor->id;
            }
        }

        $source->update($data);
    }

    private function syncSupply(SupplyRequest $source, ?User $actor): void
    {
        if ($source->status === 'completada') {
            return;
        }

        if (! in_array($source->status, ['aprobada_calidad', 'en_compras'], true)) {
            return;
        }

        $next = match ($this->nextComprasState(PurchaseOrderService::SOURCE_SUPPLY, $source)) {
            PurchaseRequest::COMPRAS_EN_CURSO => 'en_compras',
            PurchaseRequest::COMPRAS_COMPLETADO => 'completada',
            default => 'aprobada_calidad',
        };

        if ($next === $source->status) {
            return;
        }

        $data = ['status' => $next];

        if ($actor !== null && in_array($next, ['en_compras', 'completada'], true)) {
            $data['purchasing_manager_id'] = $actor->id;
        }

        $source->update($data);
    }

    private function nextComprasState(string $sourceType, PurchaseRequest|SupplyRequest $source): string
    {
        $ordered = 0.0;
        $pending = 0.0;

        foreach ($this->orders->sourceLines($sourceType, $source) as $line) {
            $ordered += (float) $line['cantidad_ordenada'];
            $pending += (float) $line['cantidad_pendiente'];
        }

        if ($ordered <= 0.001) {
            return PurchaseRequest::COMPRAS_PENDIENTE;
        }

        if ($pending > 0.001) {
            return PurchaseRequest::COMPRAS_EN_CURSO;
        }

        // Cerrado solo si el saldo es cero y cada OC del origen tiene fecha de recibido.
        // Cerrar saldo no pasa por aquí: esa acción cierra aunque falten fechas.
        if (! $this->allOrdersHaveReceiptDate($sourceType, (int) $source->getKey())) {
            return PurchaseRequest::COMPRAS_EN_CURSO;
        }

        return PurchaseRequest::COMPRAS_COMPLETADO;
    }

    /**
     * Todas las OC del origen tienen fecha de recibido. El puntaje no cuenta.
     */
    private function allOrdersHaveReceiptDate(string $sourceType, int $sourceId): bool
    {
        $orders = PurchaseOrder::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->with('evaluation')
            ->get();

        if ($orders->isEmpty()) {
            return false;
        }

        return $orders->every(
            fn (PurchaseOrder $order): bool => $order->evaluation?->fecha_recibido !== null,
        );
    }

    private function appendMotivo(PurchaseRequest|SupplyRequest $source, User $actor, string $motivo): void
    {
        if ($source instanceof PurchaseRequest) {
            PurchaseRequestComment::query()->create([
                'purchase_request_id' => $source->id,
                'user_id' => $actor->id,
                'body' => $motivo,
            ]);

            return;
        }

        SupplyRequestComment::query()->create([
            'supply_request_id' => $source->id,
            'user_id' => $actor->id,
            'body' => $motivo,
        ]);
    }
}
