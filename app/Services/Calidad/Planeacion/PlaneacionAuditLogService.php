<?php

namespace App\Services\Calidad\Planeacion;

use App\Services\Audit\SystemAuditService;
use Illuminate\Database\Eloquent\Model;

class PlaneacionAuditLogService
{
    private const MODULE = 'planeacion_estrategica';

    private const AREA = 'calidad';

    public function __construct(
        private readonly SystemAuditService $systemAuditService,
    ) {}

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $metadata
     */
    public function logModelChange(
        string $eventType,
        string $action,
        ?Model $model,
        ?array $before,
        ?array $after,
        ?string $reason = null,
        array $metadata = [],
        ?int $userId = null,
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
            userId: $userId,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
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
