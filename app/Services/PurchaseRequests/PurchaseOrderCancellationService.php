<?php

namespace App\Services\PurchaseRequests;

use App\Mail\PurchaseOrderCancellationRequestedMail;
use App\Mail\PurchaseOrderCancellationResolvedMail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestComment;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestComment;
use App\Models\User;
use App\Services\Access\PurchaseAccessService;
use App\Support\ApplicationUrls;
use App\Support\UppercaseFormText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

/**
 * Anulación de una OC con aprobación de un director elegido por Compras.
 *
 * Emitida → Anulación pendiente → Anulada (aprobada) o Emitida (rechazada / retirada).
 * La OC anulada no se borra: deja de contar en el saldo y la máquina recalcula el pedido.
 */
class PurchaseOrderCancellationService
{
    public function __construct(
        private readonly PurchaseFlowStateService $flowState,
        private readonly PurchaseRequestAuditLogService $audit,
        private readonly PurchaseAccessService $access,
    ) {}

    public function request(PurchaseOrder $order, User $actor, int $directorId, string $motivo): PurchaseOrder
    {
        $motivo = $this->normalize($motivo);

        if ($motivo === '') {
            throw new InvalidArgumentException('Indique el motivo de la anulación.');
        }

        if (! $order->puedeSolicitarAnulacion()) {
            throw new InvalidArgumentException('Solo se puede pedir la anulación de una orden emitida sin fecha de recibido.');
        }

        if (! $this->access->isValidApprover($directorId)) {
            throw new InvalidArgumentException('Seleccione un director activo con permiso para autorizar.');
        }

        DB::transaction(function () use ($order, $actor, $directorId, $motivo): void {
            $order->update([
                'estado' => PurchaseOrder::ESTADO_ANULACION_PENDIENTE,
                'anulacion_aprobador_id' => $directorId,
                'anulacion_solicitada_por' => $actor->id,
                'anulacion_motivo' => $motivo,
                'anulacion_solicitada_at' => now(),
                'anulacion_respuesta' => null,
                'anulacion_resuelta_at' => null,
                'anulacion_resultado' => null,
            ]);

            $this->comment($order, $actor, 'SOLICITUD DE ANULACIÓN OC N.º '.$order->numeroOc.': '.$motivo);
        });

        $order->refresh();

        $this->audit->logModelChange(
            eventType: 'purchase_order',
            action: 'cancellation_requested',
            model: $order,
            before: ['estado' => PurchaseOrder::ESTADO_EMITIDA],
            after: ['estado' => $order->estado, 'anulacion_aprobador_id' => $directorId],
            reason: $motivo,
            metadata: ['numero_oc' => $order->numeroOc],
            userId: $actor->id,
        );

        $this->notifyDirector($order);

        return $order;
    }

    public function withdraw(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        if (! $order->tieneAnulacionPendiente()) {
            throw new InvalidArgumentException('La orden no tiene una solicitud de anulación pendiente.');
        }

        $directorId = $order->anulacion_aprobador_id;

        DB::transaction(function () use ($order, $actor): void {
            $order->update([
                'estado' => PurchaseOrder::ESTADO_EMITIDA,
                'anulacion_aprobador_id' => null,
                'anulacion_solicitada_por' => null,
                'anulacion_motivo' => null,
                'anulacion_solicitada_at' => null,
                'anulacion_respuesta' => null,
                'anulacion_resuelta_at' => null,
                'anulacion_resultado' => null,
            ]);

            $this->comment($order, $actor, 'SOLICITUD DE ANULACIÓN OC N.º '.$order->numeroOc.' RETIRADA POR COMPRAS.');
        });

        $this->audit->logModelChange(
            eventType: 'purchase_order',
            action: 'cancellation_withdrawn',
            model: $order,
            before: ['estado' => PurchaseOrder::ESTADO_ANULACION_PENDIENTE, 'anulacion_aprobador_id' => $directorId],
            after: ['estado' => PurchaseOrder::ESTADO_EMITIDA],
            metadata: ['numero_oc' => $order->numeroOc],
            userId: $actor->id,
        );

        return $order->refresh();
    }

    /**
     * El director elegido aprueba o rechaza. `channel`: web | email.
     */
    public function resolve(PurchaseOrder $order, int $directorId, bool $approve, ?string $comentario, string $channel = 'web'): PurchaseOrder
    {
        if (! $order->tieneAnulacionPendiente()) {
            throw new InvalidArgumentException('Esta solicitud de anulación ya fue gestionada.');
        }

        if ((int) $order->anulacion_aprobador_id !== $directorId) {
            throw new InvalidArgumentException('El director no está asignado a esta solicitud de anulación.');
        }

        $comentario = $this->normalize((string) $comentario);

        if (! $approve && $comentario === '') {
            throw new InvalidArgumentException('Indique el motivo del rechazo.');
        }

        $director = User::query()->findOrFail($directorId);

        DB::transaction(function () use ($order, $director, $approve, $comentario): void {
            $order->update([
                'estado' => $approve ? PurchaseOrder::ESTADO_ANULADA : PurchaseOrder::ESTADO_EMITIDA,
                'anulacion_respuesta' => $comentario !== '' ? $comentario : null,
                'anulacion_resuelta_at' => now(),
                'anulacion_resultado' => $approve ? PurchaseOrder::APROBACION_APROBADA : PurchaseOrder::APROBACION_RECHAZADA,
            ]);

            $decision = $approve ? 'APROBADA' : 'RECHAZADA';
            $body = 'ANULACIÓN OC N.º '.$order->numeroOc.' '.$decision.' POR '.UppercaseFormText::uppercaseString((string) $director->name)
                .($comentario !== '' ? ': '.$comentario : '.');

            $this->comment($order, $director, $body);
        });

        $order->refresh()->loadMissing('source');

        // Al anular, la cantidad se libera y el pedido vuelve a Con orden o Por comprar (nunca reabre un Cerrado).
        if ($approve && ($order->source instanceof PurchaseRequest || $order->source instanceof SupplyRequest)) {
            $this->flowState->sync($order->source->fresh(), $director);
        }

        $this->audit->logModelChange(
            eventType: 'purchase_order',
            action: $approve ? 'cancellation_approved' : 'cancellation_rejected',
            model: $order,
            before: ['estado' => PurchaseOrder::ESTADO_ANULACION_PENDIENTE],
            after: ['estado' => $order->estado],
            reason: $comentario !== '' ? $comentario : null,
            metadata: ['numero_oc' => $order->numeroOc, 'channel' => $channel],
            userId: $director->id,
        );

        $this->notifyRequester($order, $approve);

        return $order;
    }

    public function emailShowUrl(PurchaseOrder $order): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.order-cancellation-email.show',
            $this->linkExpiresAt(),
            ['purchase_order' => $order->getKey(), 'director' => $order->anulacion_aprobador_id],
        );
    }

    public function emailUpdateUrl(PurchaseOrder $order): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.order-cancellation-email.update',
            $this->linkExpiresAt(),
            ['purchase_order' => $order->getKey(), 'director' => $order->anulacion_aprobador_id],
        );
    }

    private function linkExpiresAt(): \DateTimeInterface
    {
        return now()->addDays(max(1, (int) config('purchase-orders.cancellation_link_days', 7)));
    }

    private function notifyDirector(PurchaseOrder $order): void
    {
        $order->loadMissing(['anulacionAprobador', 'anulacionSolicitadaPor', 'source']);
        $email = $order->anulacionAprobador?->email;

        if (! filled($email)) {
            return;
        }

        $this->send($order, $email, new PurchaseOrderCancellationRequestedMail($order, $this->emailShowUrl($order)));
    }

    private function notifyRequester(PurchaseOrder $order, bool $approved): void
    {
        $order->loadMissing(['anulacionAprobador', 'anulacionSolicitadaPor', 'source']);
        $email = $order->anulacionSolicitadaPor?->email;

        if (! filled($email)) {
            return;
        }

        $this->send($order, $email, new PurchaseOrderCancellationResolvedMail($order, $approved));
    }

    private function send(PurchaseOrder $order, string $email, object $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar el correo de anulación de la orden de compra', [
                'purchase_order_id' => $order->getKey(),
                'recipient' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * La trazabilidad queda en el hilo del pedido de origen (compra o suministro).
     */
    private function comment(PurchaseOrder $order, User $author, string $body): void
    {
        $order->loadMissing('source');
        $source = $order->source;

        if ($source instanceof PurchaseRequest) {
            PurchaseRequestComment::query()->create([
                'purchase_request_id' => $source->id,
                'user_id' => $author->id,
                'body' => $body,
            ]);

            return;
        }

        if ($source instanceof SupplyRequest) {
            SupplyRequestComment::query()->create([
                'supply_request_id' => $source->id,
                'user_id' => $author->id,
                'body' => $body,
            ]);
        }
    }

    private function normalize(string $text): string
    {
        return trim(UppercaseFormText::uppercaseString(trim($text)));
    }
}
