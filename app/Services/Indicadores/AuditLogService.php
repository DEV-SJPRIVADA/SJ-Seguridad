<?php

namespace App\Services\Indicadores;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function logModelChange(
        string $eventType,
        string $action,
        ?Model $model,
        ?array $before,
        ?array $after,
        ?string $reason = null,
        array $metadata = []
    ): void {
        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'action' => $action,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'old_values' => $before,
            'new_values' => $after,
            'metadata' => $metadata,
            'reason' => $reason,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function logEvent(
        string $eventType,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?Model $model = null
    ): void {
        $this->logModelChange(
            eventType: $eventType,
            action: $action,
            model: $model,
            before: null,
            after: null,
            reason: $reason,
            metadata: $metadata
        );
    }
}
