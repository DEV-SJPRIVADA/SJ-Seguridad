<?php

namespace App\Services\Calidad;

use App\Models\AuditLog;
use App\Models\Calidad\MtGi11Solicitud;
use App\Services\Audit\SystemAuditService;
use App\Support\DisplayDate;

/**
 * Auditoría del tablero MT-GI-11 sobre el log central.
 */
final class MtGi11AuditLogService
{
    private const MODULE = 'mt_gi_11';

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
        string $action,
        MtGi11Solicitud $solicitud,
        ?array $before,
        ?array $after,
        ?int $userId = null,
    ): void {
        $this->systemAuditService->logModelChange(
            module: self::MODULE,
            eventType: 'mt_gi_11',
            action: $action,
            model: $solicitud,
            before: $before,
            after: $after,
            metadata: ['consecutivo' => (string) $solicitud->consecutivo, 'solicitud_id' => (int) $solicitud->id],
            area: self::AREA,
            userId: $userId,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function logEvent(
        string $action,
        string $eventType,
        array $metadata = [],
        ?int $userId = null,
    ): void {
        $this->systemAuditService->logEvent(
            module: self::MODULE,
            eventType: $eventType,
            action: $action,
            metadata: $metadata,
            area: self::AREA,
            userId: $userId,
        );
    }

    /**
     * Historial legible para el modal de cambios.
     *
     * @return list<array{id: int, kind: string, action_label: string, user_name: string|null, created_at_display: string, summary: string}>
     */
    public function history(int $limit = 100): array
    {
        return AuditLog::query()
            ->forModule(self::MODULE)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(max(1, min(200, $limit)))
            ->get()
            ->map(function (AuditLog $log): array {
                $kind = $this->historyKind((string) $log->action, (string) $log->event_type);
                $meta = is_array($log->metadata) ? $log->metadata : [];
                $consecutivo = $meta['consecutivo'] ?? '';

                return [
                    'id' => (int) $log->id,
                    'kind' => $kind,
                    'action_label' => $this->historyActionLabel($kind),
                    'user_name' => $log->user?->name,
                    'created_at_display' => DisplayDate::dateTime($log->created_at),
                    'summary' => trim($this->historyActionLabel($kind).($consecutivo !== '' ? ' de la solicitud '.$consecutivo : '')),
                ];
            })
            ->values()
            ->all();
    }

    private function historyKind(string $action, string $eventType): string
    {
        if ($eventType === 'export' || str_ends_with($action, '_excel')) {
            return 'review';
        }

        if (str_starts_with($eventType, 'import')) {
            return 'import';
        }

        return in_array($action, ['create', 'update', 'delete'], true) ? $action : 'update';
    }

    private function historyActionLabel(string $kind): string
    {
        return match ($kind) {
            'create' => 'Alta',
            'delete' => 'Eliminación',
            'review' => 'Exportación',
            'import' => 'Importación',
            default => 'Actualización',
        };
    }
}
