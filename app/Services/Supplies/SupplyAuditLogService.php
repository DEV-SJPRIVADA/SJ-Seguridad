<?php

namespace App\Services\Supplies;

use App\Services\Audit\SystemAuditService;
use Illuminate\Database\Eloquent\Model;

class SupplyAuditLogService
{
    private const MODULE = 'supplies';

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
        array $metadata = [],
        ?int $userId = null,
        ?string $area = null,
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
            area: $area,
            userId: $userId,
        );
    }

    public function logEvent(
        string $eventType,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?Model $model = null,
        ?int $userId = null,
        ?string $area = null,
    ): void {
        $this->systemAuditService->logEvent(
            module: self::MODULE,
            eventType: $eventType,
            action: $action,
            reason: $reason,
            metadata: $metadata,
            model: $model,
            area: $area,
            userId: $userId,
        );
    }
}
