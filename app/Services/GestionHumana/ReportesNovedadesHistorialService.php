<?php

namespace App\Services\GestionHumana;

use App\Models\AuditLog;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ReportesNovedadesHistorialService
{
    /**
     * @param  class-string<Model>  $auditableType
     * @return list<array{id: int, action: string, event_type: string, user_name: string|null, created_at: string|null, created_at_display: string, summary: string, reason: string|null}>
     */
    public function forSheet(string $sheet, string $auditableType, ?int $rowId = null, int $limit = 100): array
    {
        /** @var Collection<int, AuditLog> $logs */
        $logs = AuditLog::query()
            ->forModule('reportes_novedades')
            ->with(['user:id,name'])
            ->where(function ($query) use ($sheet, $auditableType): void {
                $query->where('auditable_type', $auditableType)
                    ->orWhere('metadata->sheet', $sheet)
                    ->orWhere('event_type', $sheet.'_novedad')
                    ->orWhere(function ($export) use ($sheet): void {
                        $export->where('event_type', 'export')
                            ->where('action', $sheet.'_excel');
                    });
            })
            ->when($rowId !== null, function ($query) use ($rowId, $auditableType): void {
                $query->where(function ($inner) use ($rowId, $auditableType): void {
                    $inner->where(function ($match) use ($rowId, $auditableType): void {
                        $match->where('auditable_type', $auditableType)
                            ->where('auditable_id', $rowId);
                    })->orWhere('metadata->id', $rowId);
                });
            })
            ->orderByDesc('id')
            ->limit(max(1, min(200, $limit)))
            ->get();

        return $logs
            ->map(fn (AuditLog $log): array => [
                'id' => (int) $log->id,
                'action' => (string) $log->action,
                'event_type' => (string) $log->event_type,
                'user_name' => $log->user?->name,
                'created_at' => optional($log->created_at)?->toIso8601String(),
                'created_at_display' => DisplayDate::dateTime($log->created_at),
                'summary' => $this->summarize($log),
                'reason' => $log->reason,
            ])
            ->values()
            ->all();
    }

    private function summarize(AuditLog $log): string
    {
        $action = (string) $log->action;
        $meta = is_array($log->metadata) ? $log->metadata : [];
        $document = (string) ($meta['document_number'] ?? '');
        $sheet = (string) ($meta['sheet'] ?? '');

        $parts = [ucfirst($action)];
        if ($sheet !== '') {
            $parts[] = $sheet;
        }
        if ($document !== '') {
            $parts[] = 'cédula '.$document;
        }

        $changed = [];
        $after = is_array($log->new_values) ? $log->new_values : [];
        $before = is_array($log->old_values) ? $log->old_values : [];
        foreach ($after as $key => $value) {
            if (($before[$key] ?? null) != $value) {
                $changed[] = (string) $key;
            }
        }
        if ($changed !== []) {
            $parts[] = 'campos: '.implode(', ', array_slice($changed, 0, 6));
        }

        return implode(' · ', $parts);
    }
}
