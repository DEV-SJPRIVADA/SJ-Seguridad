<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Services\Notifications\NotificationConfigService;
use InvalidArgumentException;

class PurchaseApprovalService
{
    public function __construct(
        private readonly PurchaseRequestNotificationService $notifications,
        private readonly NotificationConfigService $notificationConfig,
    ) {}

    public function resolve(
        PurchaseRequest $purchaseRequest,
        string $estado,
        int $aprobadorId,
        ?string $comentariosDirector = null,
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

        return $purchaseRequest;
    }
}
