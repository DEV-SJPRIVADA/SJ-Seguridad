<?php

namespace App\Services\Audit;

use App\Jobs\WriteAuditLogJob;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SystemAuditService
{
    public function logModelChange(
        string $module,
        string $eventType,
        string $action,
        ?Model $model,
        ?array $before,
        ?array $after,
        ?string $reason = null,
        array $metadata = [],
        ?string $area = null,
        ?string $changeBatch = null,
    ): void {
        if (! config('audit.enabled', true)) {
            return;
        }

        $payload = $this->buildPayload(
            module: $module,
            area: $area,
            eventType: $eventType,
            action: $action,
            model: $model,
            before: $before,
            after: $after,
            reason: $reason,
            metadata: $metadata,
            changeBatch: $changeBatch,
        );

        $this->persist($payload);
    }

    public function logEvent(
        string $module,
        string $eventType,
        string $action,
        ?string $reason = null,
        array $metadata = [],
        ?Model $model = null,
        ?string $area = null,
        ?string $changeBatch = null,
    ): void {
        $this->logModelChange(
            module: $module,
            eventType: $eventType,
            action: $action,
            model: $model,
            before: null,
            after: null,
            reason: $reason,
            metadata: $metadata,
            area: $area,
            changeBatch: $changeBatch,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(
        string $module,
        ?string $area,
        string $eventType,
        string $action,
        ?Model $model,
        ?array $before,
        ?array $after,
        ?string $reason,
        array $metadata,
        ?string $changeBatch,
    ): array {
        $truncatedFields = [];
        $before = $this->truncateJsonField($before, $truncatedFields, 'old_values');
        $after = $this->truncateJsonField($after, $truncatedFields, 'new_values');
        $metadata = $this->truncateJsonField($metadata, $truncatedFields, 'metadata') ?? [];

        if ($truncatedFields !== []) {
            $metadata['_truncated_fields'] = $truncatedFields;
        }

        return [
            'module' => $module,
            'area' => $area,
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'action' => $action,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'change_batch' => $changeBatch,
            'old_values' => $before,
            'new_values' => $after,
            'metadata' => $metadata,
            'reason' => $reason,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<int, string>  $truncatedFields
     * @return array<string, mixed>|null
     */
    private function truncateJsonField(?array $data, array &$truncatedFields, string $fieldName): ?array
    {
        if ($data === null) {
            return null;
        }

        $encoded = json_encode($data);
        $maxBytes = (int) config('audit.max_json_bytes', 65536);

        if ($encoded !== false && strlen($encoded) <= $maxBytes) {
            return $data;
        }

        $truncatedFields[] = $fieldName;

        return ['_truncated' => true, 'preview' => Str::limit($encoded ?: '', 500)];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function persist(array $payload): void
    {
        if (config('audit.queue', false)) {
            WriteAuditLogJob::dispatch($payload)
                ->onConnection(config('audit.connection'))
                ->afterCommit();

            return;
        }

        AuditLog::query()->create($payload);
    }
}
