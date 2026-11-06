<?php

namespace App\Services\Calidad;

use App\Models\AuditLog;
use App\Services\Audit\SystemAuditService;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Model;

class ServicioClienteAuditLogService
{
    private const MODULE = 'servicio_cliente';

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

    /**
     * @return list<array{id: int, action: string, kind: string, action_label: string, user_name: string|null, created_at_display: string, summary: string, reason: string|null}>
     */
    public function history(int $limit = 100): array
    {
        return AuditLog::query()
            ->forModule(self::MODULE)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(max(1, min(200, $limit)))
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => (int) $log->id,
                'action' => (string) $log->action,
                'kind' => $this->historyKind((string) $log->action, (string) $log->event_type),
                'action_label' => $this->historyActionLabel((string) $log->action, (string) $log->event_type),
                'user_name' => $log->user?->name,
                'created_at_display' => DisplayDate::dateTime($log->created_at),
                'summary' => $this->historySummary($log),
                'reason' => $log->reason,
            ])
            ->values()
            ->all();
    }

    private function historyKind(string $action, string $eventType): string
    {
        if ($eventType === 'export' || str_ends_with($action, '_excel')) {
            return 'review';
        }

        return in_array($action, ['create', 'update', 'delete'], true) ? $action : 'update';
    }

    private function historyActionLabel(string $action, string $eventType): string
    {
        return match ($this->historyKind($action, $eventType)) {
            'create' => 'Alta',
            'delete' => 'Eliminación',
            'review' => 'Exportación',
            default => 'Actualización',
        };
    }

    private function historySummary(AuditLog $log): string
    {
        $meta = is_array($log->metadata) ? $log->metadata : [];
        $consecutivo = $meta['consecutivo'] ?? null;
        $caso = $consecutivo ? 'caso N° '.$consecutivo : 'caso';

        return match ((string) $log->event_type) {
            'fo_gi_22' => $this->historyActionLabel((string) $log->action, (string) $log->event_type).' del '.$caso,
            'fo_gi_22_observacion' => $this->historyActionLabel((string) $log->action, (string) $log->event_type).' de una observación del '.$caso,
            'fo_gi_22_adjunto' => $this->historyActionLabel((string) $log->action, (string) $log->event_type).' de un adjunto del '.$caso,
            'servicio_cliente_catalog' => $this->historyActionLabel((string) $log->action, (string) $log->event_type).' de catálogo '.($meta['catalog_type'] ?? ''),
            default => $this->historyActionLabel((string) $log->action, (string) $log->event_type),
        };
    }
}
