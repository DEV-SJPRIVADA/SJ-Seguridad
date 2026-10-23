<?php

namespace App\Services\PurchaseRequests;

use App\Mail\PurchaseOrderApprovalRequestedMail;
use App\Mail\PurchaseOrderApprovalResolvedMail;
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
 * Aprobación opcional de una OC por un director o administrador elegido por Compras.
 *
 * Sin pedir → Pendiente → Aprobada (la OC sigue Emitida y el FO-AD-07 muestra al aprobador)
 * o Rechazada (la OC queda Anulada, su cantidad vuelve al saldo y la máquina recalcula el pedido).
 */
class PurchaseOrderApprovalService
{
    public function __construct(
        private readonly PurchaseFlowStateService $flowState,
        private readonly PurchaseRequestAuditLogService $audit,
        private readonly PurchaseAccessService $access,
    ) {}

    public function request(PurchaseOrder $order, User $actor, int $approverId, ?string $nota): PurchaseOrder
    {
        if (! $order->puedeSolicitarAprobacion()) {
            throw new InvalidArgumentException('Solo se puede pedir la aprobación de una orden emitida que aún no la tenga.');
        }

        if (! $this->access->isValidApprover($approverId)) {
            throw new InvalidArgumentException('Seleccione un director o administrador activo con permiso para autorizar.');
        }

        $nota = $this->normalize((string) $nota);

        DB::transaction(function () use ($order, $actor, $approverId, $nota): void {
            $order->update([
                'aprobacion_estado' => PurchaseOrder::APROBACION_PENDIENTE,
                'aprobacion_aprobador_id' => $approverId,
                'aprobacion_solicitada_por' => $actor->id,
                'aprobacion_nota' => $nota !== '' ? $nota : null,
                'aprobacion_solicitada_at' => now(),
                'aprobacion_comentario' => null,
                'aprobacion_resuelta_at' => null,
            ]);

            $this->comment($order, $actor, 'SOLICITUD DE APROBACIÓN OC N.º '.$order->numeroOc.($nota !== '' ? ': '.$nota : '.'));
        });

        $order->refresh();

        $this->audit->logModelChange(
            eventType: 'purchase_order',
            action: 'approval_requested',
            model: $order,
            before: ['aprobacion_estado' => null],
            after: ['aprobacion_estado' => $order->aprobacion_estado, 'aprobacion_aprobador_id' => $approverId],
            reason: $nota !== '' ? $nota : null,
            metadata: ['numero_oc' => $order->numeroOc],
            userId: $actor->id,
        );

        $this->notifyApprover($order);

        return $order;
    }

    public function withdraw(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        if (! $order->tieneAprobacionPendiente()) {
            throw new InvalidArgumentException('La orden no tiene una solicitud de aprobación pendiente.');
        }

        $approverId = $order->aprobacion_aprobador_id;

        DB::transaction(function () use ($order, $actor): void {
            $order->update([
                'aprobacion_estado' => null,
                'aprobacion_aprobador_id' => null,
                'aprobacion_solicitada_por' => null,
                'aprobacion_nota' => null,
                'aprobacion_solicitada_at' => null,
                'aprobacion_comentario' => null,
                'aprobacion_resuelta_at' => null,
            ]);

            $this->comment($order, $actor, 'SOLICITUD DE APROBACIÓN OC N.º '.$order->numeroOc.' RETIRADA POR COMPRAS.');
        });

        $this->audit->logModelChange(
            eventType: 'purchase_order',
            action: 'approval_withdrawn',
            model: $order,
            before: ['aprobacion_estado' => PurchaseOrder::APROBACION_PENDIENTE, 'aprobacion_aprobador_id' => $approverId],
            after: ['aprobacion_estado' => null],
            metadata: ['numero_oc' => $order->numeroOc],
            userId: $actor->id,
        );

        return $order->refresh();
    }

    /**
     * El aprobador elegido decide. Rechazar exige comentario y anula la OC. `channel`: web | email.
     */
    public function resolve(PurchaseOrder $order, int $approverId, bool $approve, ?string $comentario, string $channel = 'web'): PurchaseOrder
    {
        if (! $order->tieneAprobacionPendiente()) {
            throw new InvalidArgumentException('Esta solicitud de aprobación ya fue gestionada.');
        }

        if ((int) $order->aprobacion_aprobador_id !== $approverId) {
            throw new InvalidArgumentException('Usted no está asignado para aprobar esta orden.');
        }

        $comentario = $this->normalize((string) $comentario);

        if (! $approve && $comentario === '') {
            throw new InvalidArgumentException('Indique el motivo del rechazo.');
        }

        $approver = User::query()->findOrFail($approverId);
        $previousEstado = $order->estado;

        DB::transaction(function () use ($order, $approver, $approve, $comentario): void {
            $order->update([
                'aprobacion_estado' => $approve ? PurchaseOrder::APROBACION_APROBADA : PurchaseOrder::APROBACION_RECHAZADA,
                'aprobacion_comentario' => $comentario !== '' ? $comentario : null,
                'aprobacion_resuelta_at' => now(),
                // Rechazada: la orden queda Anulada para emitir una nueva corregida.
                'estado' => $approve ? $order->estado : PurchaseOrder::ESTADO_ANULADA,
            ]);

            $decision = $approve ? 'APROBADA' : 'RECHAZADA (ORDEN ANULADA)';
            $body = 'OC N.º '.$order->numeroOc.' '.$decision.' POR '.UppercaseFormText::uppercaseString((string) $approver->name)
                .($comentario !== '' ? ': '.$comentario : '.');

            $this->comment($order, $approver, $body);
        });

        $order->refresh()->loadMissing('source');

        // Al rechazar, la cantidad se libera y el pedido vuelve a Con orden o Por comprar (nunca reabre un Cerrado).
        if (! $approve && ($order->source instanceof PurchaseRequest || $order->source instanceof SupplyRequest)) {
            $this->flowState->sync($order->source->fresh(), $approver);
        }

        $this->audit->logModelChange(
            eventType: 'purchase_order',
            action: $approve ? 'approval_approved' : 'approval_rejected',
            model: $order,
            before: ['aprobacion_estado' => PurchaseOrder::APROBACION_PENDIENTE, 'estado' => $previousEstado],
            after: ['aprobacion_estado' => $order->aprobacion_estado, 'estado' => $order->estado],
            reason: $comentario !== '' ? $comentario : null,
            metadata: ['numero_oc' => $order->numeroOc, 'channel' => $channel],
            userId: $approver->id,
        );

        $this->notifyRequester($order, $approve);

        return $order;
    }

    public function emailShowUrl(PurchaseOrder $order): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.order-approval-email.show',
            $this->linkExpiresAt(),
            ['purchase_order' => $order->getKey(), 'director' => $order->aprobacion_aprobador_id],
        );
    }

    public function emailUpdateUrl(PurchaseOrder $order): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.order-approval-email.update',
            $this->linkExpiresAt(),
            ['purchase_order' => $order->getKey(), 'director' => $order->aprobacion_aprobador_id],
        );
    }

    private function linkExpiresAt(): \DateTimeInterface
    {
        return now()->addDays(max(1, (int) config('purchase-orders.approval_link_days', 7)));
    }

    private function notifyApprover(PurchaseOrder $order): void
    {
        $order->loadMissing(['aprobacionAprobador', 'aprobacionSolicitadaPor', 'source']);
        $email = $order->aprobacionAprobador?->email;

        if (filled($email)) {
            $this->send($order, $email, new PurchaseOrderApprovalRequestedMail($order, $this->emailShowUrl($order)));
        }
    }

    private function notifyRequester(PurchaseOrder $order, bool $approved): void
    {
        $order->loadMissing(['aprobacionAprobador', 'aprobacionSolicitadaPor', 'source']);
        $email = $order->aprobacionSolicitadaPor?->email;

        if (filled($email)) {
            $this->send($order, $email, new PurchaseOrderApprovalResolvedMail($order, $approved));
        }
    }

    private function send(PurchaseOrder $order, string $email, object $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar el correo de aprobación de la orden de compra', [
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
            PurchaseRequestComment::query()->create(['purchase_request_id' => $source->id, 'user_id' => $author->id, 'body' => $body]);

            return;
        }

        if ($source instanceof SupplyRequest) {
            SupplyRequestComment::query()->create(['supply_request_id' => $source->id, 'user_id' => $author->id, 'body' => $body]);
        }
    }

    private function normalize(string $text): string
    {
        return trim(UppercaseFormText::uppercaseString(trim($text)));
    }
}
