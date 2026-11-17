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
 * Autorización de OC en dos pasos: primero quien autorizó la solicitud (director SC o
 * Calidad SU) y después el Director Admon. que elige Compras. Hasta que no pase el
 * primero, el segundo no decide. Si es la misma persona, son dos decisiones distintas.
 */
class PurchaseOrderApprovalService
{
    public const PASO_AREA = 'area';

    public const PASO_ADMON = 'admon';

    public function __construct(
        private readonly PurchaseFlowStateService $flowState,
        private readonly PurchaseRequestAuditLogService $audit,
        private readonly PurchaseAccessService $access,
        private readonly PurchaseOrderSignatureSnapshotService $signatures,
    ) {}

    public function request(PurchaseOrder $order, User $actor, int $approverId, ?string $nota): PurchaseOrder
    {
        if (! $order->puedeReasignarAprobador()) {
            throw new InvalidArgumentException('Solo se puede asignar o cambiar el aprobador de una orden pendiente de autorización.');
        }

        if (! $this->access->isValidApprover($approverId)) {
            throw new InvalidArgumentException('Seleccione un director o administrador activo con permiso para autorizar.');
        }

        $areaApproverId = $order->sourceRequestApproverId();

        if ($areaApproverId === null) {
            throw new InvalidArgumentException('La solicitud de origen no tiene quien la haya autorizado. No se puede enviar la orden.');
        }

        $nota = $this->normalize((string) $nota);
        $previousApproverId = $order->aprobacion_aprobador_id;
        $keepAreaDecision = $order->pasoAreaAprobado() && (int) $order->aprobacion_area_aprobador_id === $areaApproverId;

        DB::transaction(function () use ($order, $actor, $approverId, $areaApproverId, $nota, $keepAreaDecision): void {
            $payload = [
                'aprobacion_estado' => PurchaseOrder::APROBACION_PENDIENTE,
                'aprobacion_aprobador_id' => $approverId,
                'aprobacion_solicitada_por' => $actor->id,
                'aprobacion_nota' => $nota !== '' ? $nota : null,
                'aprobacion_solicitada_at' => now(),
                'aprobacion_comentario' => null,
                'aprobacion_resuelta_at' => null,
                'aprobacion_area_aprobador_id' => $areaApproverId,
            ];

            if (! $keepAreaDecision) {
                $payload['aprobacion_area_estado'] = PurchaseOrder::APROBACION_PENDIENTE;
                $payload['aprobacion_area_comentario'] = null;
                $payload['aprobacion_area_resuelta_at'] = null;
            }

            $order->update($payload);

            $this->comment($order, $actor, 'ORDEN OC N.º '.$order->numeroOc.' ENVIADA A AUTORIZACIÓN'.($nota !== '' ? ': '.$nota : '.'));
        });

        $order->refresh();

        $this->audit->logModelChange(
            eventType: 'purchase_order',
            action: $previousApproverId === null ? 'approval_requested' : 'approval_reassigned',
            model: $order,
            before: ['aprobacion_estado' => null, 'aprobacion_aprobador_id' => $previousApproverId],
            after: ['aprobacion_estado' => $order->aprobacion_estado, 'aprobacion_aprobador_id' => $approverId],
            reason: $nota !== '' ? $nota : null,
            metadata: [
                'numero_oc' => $order->numeroOc,
                'aprobacion_area_aprobador_id' => $areaApproverId,
            ],
            userId: $actor->id,
        );

        $this->notifyApprovers($order, $previousApproverId);

        return $order;
    }

    /**
     * El aprobador del paso que le corresponde decide.
     * Paso 1: no emite la OC. Paso 2 o rechazo: emite o anula (`channel`: web | email).
     */
    public function resolve(PurchaseOrder $order, int $approverId, bool $approve, ?string $comentario, string $channel = 'web'): PurchaseOrder
    {
        if (! $order->tieneAprobacionPendiente()) {
            throw new InvalidArgumentException('Esta solicitud de aprobación ya fue gestionada.');
        }

        if (! $order->esAprobadorAsignado($approverId)) {
            throw new InvalidArgumentException('Usted no está asignado para aprobar esta orden.');
        }

        $comentario = $this->normalize((string) $comentario);

        if (! $approve && $comentario === '') {
            throw new InvalidArgumentException('Indique el motivo del rechazo.');
        }

        if ($order->puedeDecidirAprobacion($approverId) && $order->esAprobadorDeSolicitud($approverId) && $order->pasoAreaPendiente()) {
            return $this->resolveAreaStep($order, $approverId, $approve, $comentario, $channel);
        }

        if ($order->esperaPasoArea($approverId) || ($order->esAprobadorAdmon($approverId) && $order->pasoAreaPendiente())) {
            $nombre = $order->aprobacionAreaAprobador?->name ?? 'quien autorizó la solicitud';

            throw new InvalidArgumentException('Espere la autorización de '.$nombre.' antes de decidir.');
        }

        if (! $order->esAprobadorAdmon($approverId) || ! $order->puedeDecidirAprobacion($approverId)) {
            throw new InvalidArgumentException('Usted no está asignado para aprobar esta orden.');
        }

        return $this->resolveAdmonStep($order, $approverId, $approve, $comentario, $channel);
    }

    public function emailShowUrl(PurchaseOrder $order, ?int $approverId = null): string
    {
        $director = $approverId ?? $order->aprobacion_aprobador_id;

        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.order-approval-email.show',
            $this->linkExpiresAt(),
            ['purchase_order' => $order->getKey(), 'director' => $director],
        );
    }

    public function emailUpdateUrl(PurchaseOrder $order, ?int $approverId = null): string
    {
        $director = $approverId ?? $order->aprobacion_aprobador_id;

        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.order-approval-email.update',
            $this->linkExpiresAt(),
            ['purchase_order' => $order->getKey(), 'director' => $director],
        );
    }

    private function resolveAreaStep(PurchaseOrder $order, int $approverId, bool $approve, string $comentario, string $channel): PurchaseOrder
    {
        $approver = User::query()->findOrFail($approverId);
        $previousEstado = $order->estado;

        DB::transaction(function () use ($order, $approver, $approve, $comentario): void {
            $order->update([
                'aprobacion_area_estado' => $approve ? PurchaseOrder::APROBACION_APROBADA : PurchaseOrder::APROBACION_RECHAZADA,
                'aprobacion_area_comentario' => $comentario !== '' ? $comentario : null,
                'aprobacion_area_resuelta_at' => now(),
                'aprobacion_estado' => $approve ? PurchaseOrder::APROBACION_PENDIENTE : PurchaseOrder::APROBACION_RECHAZADA,
                'aprobacion_comentario' => $approve ? null : ($comentario !== '' ? $comentario : null),
                'aprobacion_resuelta_at' => $approve ? null : now(),
                'estado' => $approve ? PurchaseOrder::ESTADO_PENDIENTE_APROBACION : PurchaseOrder::ESTADO_ANULADA,
            ]);

            if ($approve) {
                $this->signatures->captureSlot($order, PurchaseOrderSignatureSnapshotService::SLOT_REVISOR, $approver);
            }

            $decision = $approve ? 'VISTO BUENO DEL ÁREA' : 'RECHAZADA POR EL ÁREA (ORDEN ANULADA)';
            $body = 'OC N.º '.$order->numeroOc.' '.$decision.' POR '.UppercaseFormText::uppercaseString((string) $approver->name)
                .($comentario !== '' ? ': '.$comentario : '.');

            $this->comment($order, $approver, $body);
        });

        $order->refresh()->loadMissing(['source', 'aprobacionAprobador', 'aprobacionAreaAprobador']);

        if (! $approve && ($order->source instanceof PurchaseRequest || $order->source instanceof SupplyRequest)) {
            $this->flowState->sync($order->source->fresh(), $approver);
        }

        $this->audit->logModelChange(
            eventType: 'purchase_order',
            action: $approve ? 'approval_area_approved' : 'approval_area_rejected',
            model: $order,
            before: ['aprobacion_area_estado' => PurchaseOrder::APROBACION_PENDIENTE, 'estado' => $previousEstado],
            after: ['aprobacion_area_estado' => $order->aprobacion_area_estado, 'estado' => $order->estado],
            reason: $comentario !== '' ? $comentario : null,
            metadata: ['numero_oc' => $order->numeroOc, 'channel' => $channel, 'paso' => self::PASO_AREA],
            userId: $approver->id,
        );

        if ($approve) {
            $this->notifyAdmonReady($order);
        } else {
            $this->notifyRequester($order, false, $approver);
        }

        return $order;
    }

    private function resolveAdmonStep(PurchaseOrder $order, int $approverId, bool $approve, string $comentario, string $channel): PurchaseOrder
    {
        $approver = User::query()->findOrFail($approverId);
        $previousEstado = $order->estado;

        DB::transaction(function () use ($order, $approver, $approve, $comentario): void {
            $order->update([
                'aprobacion_estado' => $approve ? PurchaseOrder::APROBACION_APROBADA : PurchaseOrder::APROBACION_RECHAZADA,
                'aprobacion_comentario' => $comentario !== '' ? $comentario : null,
                'aprobacion_resuelta_at' => now(),
                'estado' => $approve ? PurchaseOrder::ESTADO_EMITIDA : PurchaseOrder::ESTADO_ANULADA,
            ]);

            if ($approve) {
                $this->signatures->capture($order);
            }

            $decision = $approve ? 'AUTORIZADA' : 'RECHAZADA (ORDEN ANULADA)';
            $body = 'OC N.º '.$order->numeroOc.' '.$decision.' POR '.UppercaseFormText::uppercaseString((string) $approver->name)
                .($comentario !== '' ? ': '.$comentario : '.');

            $this->comment($order, $approver, $body);
        });

        $order->refresh()->loadMissing('source');

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
            metadata: ['numero_oc' => $order->numeroOc, 'channel' => $channel, 'paso' => self::PASO_ADMON],
            userId: $approver->id,
        );

        $this->notifyRequester($order, $approve, $approver);

        return $order;
    }

    private function linkExpiresAt(): \DateTimeInterface
    {
        return now()->addDays(max(1, (int) config('purchase-orders.approval_link_days', 7)));
    }

    /**
     * Al enviar o reasignar: correo a ambos. Si se reasigna solo el Admon., no se reenvía al área.
     */
    private function notifyApprovers(PurchaseOrder $order, mixed $previousApproverId): void
    {
        $order->loadMissing(['aprobacionAprobador', 'aprobacionAreaAprobador', 'aprobacionSolicitadaPor', 'source', 'purchaseQuotation.aprobador']);

        $areaId = (int) $order->aprobacion_area_aprobador_id;
        $admonId = (int) $order->aprobacion_aprobador_id;
        $isReassign = $previousApproverId !== null;

        if (! $isReassign && filled($order->aprobacionAreaAprobador?->email)) {
            $this->send(
                $order,
                (string) $order->aprobacionAreaAprobador->email,
                new PurchaseOrderApprovalRequestedMail(
                    $order,
                    $this->emailShowUrl($order, $areaId),
                    self::PASO_AREA,
                ),
            );
        }

        if (filled($order->aprobacionAprobador?->email)) {
            $this->send(
                $order,
                (string) $order->aprobacionAprobador->email,
                new PurchaseOrderApprovalRequestedMail(
                    $order,
                    $this->emailShowUrl($order, $admonId),
                    self::PASO_ADMON,
                    $order->pasoAreaAprobado(),
                ),
            );
        }
    }

    private function notifyAdmonReady(PurchaseOrder $order): void
    {
        $order->loadMissing(['aprobacionAprobador', 'aprobacionAreaAprobador', 'aprobacionSolicitadaPor', 'source', 'purchaseQuotation.aprobador']);
        $email = $order->aprobacionAprobador?->email;
        $admonId = (int) $order->aprobacion_aprobador_id;

        // Incluye el caso en que la misma persona hace el paso 2: se le avisa que ya puede autorizar como Admon.
        if (! filled($email) || $admonId <= 0) {
            return;
        }

        $this->send(
            $order,
            (string) $email,
            new PurchaseOrderApprovalRequestedMail(
                $order,
                $this->emailShowUrl($order, $admonId),
                self::PASO_ADMON,
                true,
            ),
        );
    }

    private function notifyRequester(PurchaseOrder $order, bool $approved, User $decider): void
    {
        $order->loadMissing(['aprobacionAprobador', 'aprobacionAreaAprobador', 'aprobacionSolicitadaPor', 'source']);
        $email = $order->aprobacionSolicitadaPor?->email;

        if (filled($email)) {
            $this->send($order, $email, new PurchaseOrderApprovalResolvedMail($order, $approved, $decider->name));
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
