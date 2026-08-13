<?php

namespace App\Services\QualityDocuments;

use App\Services\Audit\SystemAuditService;
use Illuminate\Database\Eloquent\Model;

class QualityDocumentAuditLogService
{
    private const MODULE = 'quality_documents';

    private const AREA = 'calidad';

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
