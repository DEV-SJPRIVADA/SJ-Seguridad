<?php

namespace App\Services\Indicadores;

use App\Services\Audit\SystemAuditService;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    private const MODULE = 'indicadores';

    private const AREA = 'operaciones';

    public function __construct(
        private readonly SystemAuditService $systemAuditService,
    ) {}

    public function logModelChange(
        string $eventType,
        string $action,
        ?Model $model,
        ?array $before,
        ?array $after,
        ?string $reason = null,
        array $metadata = []
    ): void {
        $this->systemAuditService->logModelChange(
            module: self::MODULE,
            eventType: $eventType,
            action: $action,
            model: $model,
            before: $before,
            after: $after,
            reason: $reason,
            metadata: $metadata,
            area: self::AREA,
        );
    }

    public function logEvent(
        string $eventType,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?Model $model = null
    ): void {
        $this->systemAuditService->logEvent(
            module: self::MODULE,
            eventType: $eventType,
            action: $action,
            reason: $reason,
            metadata: $metadata,
            model: $model,
            area: self::AREA,
        );
    }
}
