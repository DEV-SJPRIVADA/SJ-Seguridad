<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Services\Notifications\NotificationConfigService;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PurchaseApprovalService
{
    public function __construct(
        private readonly PurchaseRequestNotificationService $notifications,
        private readonly NotificationConfigService $notificationConfig,
        private readonly PurchaseRequestAuditLogService $auditLogService,
    ) {}

    public function resolve(
        PurchaseRequest $purchaseRequest,
        string $estado,
        int $aprobadorId,
        ?string $comentariosDirector = null,
        string $channel = 'web',
    ): PurchaseRequest {
        if ((int) $purchaseRequest->aprobador_id !== $aprobadorId) {
            throw new InvalidArgumentException('El director no esta asignado a esta solicitud.');
        }

        if ($purchaseRequest->estado !== PurchaseRequest::ESTADO_PENDIENTE) {
            throw new InvalidArgumentException('Esta solicitud ya fue gestionada.');
        }

        if (! in_array($estado, [PurchaseRequest::ESTADO_APROBADO, PurchaseRequest::ESTADO_RECHAZADO], true)) {
            throw new InvalidArgumentException('Estado de resolucion no valido.');
        }

        $data = [
            'estado' => $estado,
            'aprobador_id' => $aprobadorId,
            'comentarios_director' => $comentariosDirector,
            'fecha_aprobacion' => $estado === PurchaseRequest::ESTADO_APROBADO ? now()->toDateString() : null,
        ];

        if ($estado === PurchaseRequest::ESTADO_APROBADO) {
            $data['estado_compras'] = PurchaseRequest::COMPRAS_PENDIENTE;
        } else {
            $data['estado_compras'] = null;
            $data['procesado_compras_at'] = null;
            $data['procesado_compras_por'] = null;
            $data['comentarios_compras'] = null;
        }

        $purchaseRequest->update($data);
        $purchaseRequest->refresh();
        $purchaseRequest->load(['user', 'aprobador', 'items']);

        $this->notifications->notifyRequesterResolved($purchaseRequest);

        if ($estado === PurchaseRequest::ESTADO_APROBADO) {
            $this->notifications->notifyComprasApproved($purchaseRequest);
        }

        $auditAction = $estado === PurchaseRequest::ESTADO_APROBADO ? 'approve' : 'reject';
        $auditUserId = $channel === 'email' ? $aprobadorId : null;

        $this->auditLogService->logModelChange(
            eventType: 'director_approval',
            action: $auditAction,
            model: $purchaseRequest,
            before: null,
            after: null,
            metadata: [
                'folio' => $purchaseRequest->folio(),
                'numero_solicitud' => $purchaseRequest->numero_solicitud,
                'channel' => $channel,
                'comentarios_director' => Str::limit((string) $comentariosDirector, 500),
            ],
            userId: $auditUserId,
        );

        return $purchaseRequest;
    }
}
