<?php

namespace App\Services\GestionHumana;

use App\Services\Audit\SystemAuditService;
use Illuminate\Database\Eloquent\Model;

class CartasNotificacionAuditLogService
{
    private const MODULE = 'cartas_notificacion';

    private const AREA = 'gestion_humana';

    public function __construct(
        private readonly SystemAuditService $systemAuditService,
    ) {}

    public function logEvent(
        string $eventType,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?Model $model = null,
        ?int $userId = null,
    ): void {
        $this->systemAuditService->logEvent(
            module: self::MODULE,
            eventType: $eventType,
            action: $action,
            reason: $reason,
            metadata: $metadata,
            model: $model,
            area: self::AREA,
            userId: $userId,
        );
    }
}
