<?php

namespace App\Services\GestionHumana;

use App\Models\AuditLog;
use App\Models\CommercialClient;
use App\Models\SeleccionExamenOcupacional;
use App\Models\User;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Historial de auditoría de Exámenes ocupacionales (alta, cambios campo a campo, eliminación),
 * global con filtros (usuario, acción, fechas, cédula) o acotado a un registro.
 */
class SeleccionExamenHistorialService
{
    public const EVENT_TYPE = 'seleccion_examen';

    /**
     * Etiquetas UI de campos auditables. Los *_code se omiten (se muestra el *_name).
     *
     * @var array<string, string>
     */
    private const FIELD_LABELS = [
        'document_number' => 'Cédula',
        'full_name' => 'Apellidos y nombres',
        'position_name' => 'Cargo',
        'servicio_sector' => 'Servicio/sector',
        'commercial_client_id' => 'Cliente',
        'eps_name' => 'EPS',
        'afp_name' => 'Pensión (AFP)',
        'birth_date' => 'Fecha nacimiento',
        'city_name' => 'Ciudad',
        'address' => 'Dirección',
        'phone' => 'Celular',
        'marital_status_name' => 'Estado civil',
        'fecha_arl' => 'Fecha ARL',
        'estado' => 'Estado',
        'email' => 'Correo',
        'solicitud_status_name' => 'Solicitud',
        'responsable_user_id' => 'Responsable',
    ];

    private const DATE_FIELDS = ['birth_date', 'fecha_arl'];

    /**
     * @param  array{q?: string, user_id?: int|null, action?: string, date_from?: string|null, date_to?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function entries(?int $examenId = null, array $filters = [], int $limit = 150): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $userId = $filters['user_id'] ?? null;
        $action = trim((string) ($filters['action'] ?? ''));
        $dateFrom = $this->normalizeDate($filters['date_from'] ?? null);
        $dateTo = $this->normalizeDate($filters['date_to'] ?? null);

        /** @var Collection<int, AuditLog> $logs */
        $logs = $this->baseQuery()
            ->with(['user:id,name'])
            ->when($examenId !== null, function ($query) use ($examenId): void {
                $query->where(function ($inner) use ($examenId): void {
                    $inner->where(function ($match) use ($examenId): void {
                        $match->where('auditable_type', SeleccionExamenOcupacional::class)
                            ->where('auditable_id', $examenId);
                    })->orWhere('metadata->seleccion_examen_id', $examenId);
                });
            })
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('metadata->document_number', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($dateFrom !== null, fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('id')
            ->limit(max(1, min(300, $limit)))
            ->get();

        $lookups = $this->lookups($logs);

        return $logs
            ->map(fn (AuditLog $log): array => [
                'id' => (int) $log->id,
                'action' => (string) $log->action,
                'event_type' => (string) $log->event_type,
                'user_name' => $log->user?->name,
                'created_at' => optional($log->created_at)?->toIso8601String(),
                'created_at_display' => DisplayDate::dateTime($log->created_at),
                'summary' => $this->summary($log),
                'changes' => $this->changes($log, $lookups),
                'reason' => $log->reason,
            ])
            ->values()
            ->all();
    }

    /**
     * Usuarios que tienen eventos en el historial (para el filtro «Usuario»).
     *
     * @return list<array{value: string, label: string}>
     */
    public function userOptions(): array
    {
        $ids = $this->baseQuery()->whereNotNull('user_id')->distinct()->pluck('user_id');

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => (string) $user->name])
            ->values()
            ->all();
    }

    /**
     * @return array{q: string, user_id: int|null, action: string, date_from: string|null, date_to: string|null}
     */
    public function filtersFromRequest(Request $request): array
    {
        $action = trim($request->string('action')->toString());
        $userId = $request->query('user_id');

        return [
            'q' => trim($request->string('q')->toString()),
            'user_id' => $userId !== null && $userId !== '' ? (int) $userId : null,
            'action' => in_array($action, ['create', 'update', 'delete'], true) ? $action : '',
            'date_from' => $this->normalizeDate($request->input('date_from')),
            'date_to' => $this->normalizeDate($request->input('date_to')),
        ];
    }

    /**
     * @return Builder<AuditLog>
     */
    private function baseQuery(): Builder
    {
        return AuditLog::query()
            ->forModule('seleccion')
            ->where('event_type', self::EVENT_TYPE);
    }

    private function summary(AuditLog $log): string
    {
        $meta = is_array($log->metadata) ? $log->metadata : [];
        $document = (string) ($meta['document_number'] ?? '');
        $id = $meta['seleccion_examen_id'] ?? $log->auditable_id;

        $parts = [];
        if ($id) {
            $parts[] = 'Examen #'.$id;
        }
        if ($document !== '') {
            $parts[] = 'cédula '.$document;
        }

        return implode(' · ', $parts);
    }

    /**
     * Cambios campo a campo (antes → después) con valores legibles.
     *
     * @param  array{users: array<int, string>, clients: array<int, string>}  $lookups
     * @return list<array{field: string, label: string, before: string, after: string}>
     */
    private function changes(AuditLog $log, array $lookups): array
    {
        $before = is_array($log->old_values) ? $log->old_values : [];
        $after = is_array($log->new_values) ? $log->new_values : [];

        if ($after === []) {
            return [];
        }

        $changes = [];
        foreach (self::FIELD_LABELS as $field => $label) {
            if (! array_key_exists($field, $after)) {
                continue;
            }

            $old = $this->display($field, $before[$field] ?? null, $lookups);
            $new = $this->display($field, $after[$field], $lookups);

            if ($before !== [] && $old === $new) {
                continue;
            }

            $changes[] = ['field' => $field, 'label' => $label, 'before' => $old, 'after' => $new];
        }

        return $changes;
    }

    /**
     * @param  array{users: array<int, string>, clients: array<int, string>}  $lookups
     */
    private function display(string $field, mixed $value, array $lookups): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match (true) {
            $field === 'estado' => SeleccionExamenOcupacional::ESTADO_LABELS[(string) $value] ?? (string) $value,
            $field === 'responsable_user_id' => $lookups['users'][(int) $value] ?? '#'.$value,
            $field === 'commercial_client_id' => $lookups['clients'][(int) $value] ?? '#'.$value,
            in_array($field, self::DATE_FIELDS, true) => DisplayDate::date(mb_substr((string) $value, 0, 10)),
            default => (string) $value,
        };
    }

    /**
     * Precarga nombres de responsables y clientes referenciados en los logs.
     *
     * @param  Collection<int, AuditLog>  $logs
     * @return array{users: array<int, string>, clients: array<int, string>}
     */
    private function lookups(Collection $logs): array
    {
        $userIds = [];
        $clientIds = [];

        foreach ($logs as $log) {
            foreach ([$log->old_values, $log->new_values] as $values) {
                if (! is_array($values)) {
                    continue;
                }
                if (! empty($values['responsable_user_id'])) {
                    $userIds[] = (int) $values['responsable_user_id'];
                }
                if (! empty($values['commercial_client_id'])) {
                    $clientIds[] = (int) $values['commercial_client_id'];
                }
            }
        }

        return [
            'users' => $userIds === [] ? [] : User::query()->whereIn('id', array_unique($userIds))->pluck('name', 'id')->all(),
            'clients' => $clientIds === [] ? [] : CommercialClient::query()->whereIn('id', array_unique($clientIds))->pluck('name', 'id')->all(),
        ];
    }

    private function normalizeDate(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));
        if ($string === '') {
            return null;
        }

        try {
            return Carbon::parse($string)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
