<?php

namespace App\Services\Requisitions;

use App\Mail\PersonalRequisitionStatusChangedMail;
use App\Models\PersonalRequisition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;

class RequisitionManagementApprovalService
{
    public const FILTER_PENDIENTE = 'pendiente';

    public const FILTER_APROBADA = 'aprobada';

    public const FILTER_RECHAZADA = 'rechazada';

    public const FILTER_TODOS = 'todos';

    /**
     * @return list<string>
     */
    public static function filterOptions(): array
    {
        return [
            self::FILTER_PENDIENTE,
            self::FILTER_APROBADA,
            self::FILTER_RECHAZADA,
            self::FILTER_TODOS,
        ];
    }

    public function normalizeFilter(?string $filter): string
    {
        $filter = strtolower(trim((string) $filter));

        return in_array($filter, self::filterOptions(), true)
            ? $filter
            : self::FILTER_PENDIENTE;
    }

    /**
     * @return Builder<PersonalRequisition>
     */
    public function listQuery(string $filter): Builder
    {
        $pending = PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA;
        $query = PersonalRequisition::query()
            ->with(['client', 'position', 'requester', 'requestReason', 'city', 'statusLogs']);

        return match ($this->normalizeFilter($filter)) {
            self::FILTER_APROBADA => $query
                ->where('status', PersonalRequisition::STATUS_SOLICITADA)
                ->whereHas('statusLogs', fn (Builder $logs) => $logs
                    ->where('from_status', $pending)
                    ->where('to_status', PersonalRequisition::STATUS_SOLICITADA)),
            self::FILTER_RECHAZADA => $query
                ->where('status', PersonalRequisition::STATUS_CANCELADA)
                ->whereHas('statusLogs', fn (Builder $logs) => $logs
                    ->where('from_status', $pending)
                    ->where('to_status', PersonalRequisition::STATUS_CANCELADA)),
            self::FILTER_TODOS => $query->where(function (Builder $inner) use ($pending): void {
                $inner
                    ->where('status', $pending)
                    ->orWhereHas('statusLogs', fn (Builder $logs) => $logs->where('from_status', $pending));
            }),
            default => $query->where('status', $pending),
        };
    }

    /**
     * @return Collection<int, PersonalRequisition>
     */
    public function list(string $filter): Collection
    {
        $pending = PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA;

        return $this->listQuery($filter)
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [$pending])
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->get();
    }

    public function passedThroughManagementApproval(PersonalRequisition $requisition): bool
    {
        if ($requisition->status === PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA) {
            return true;
        }

        return $requisition->statusLogs()
            ->where('from_status', PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA)
            ->exists();
    }

    public function resolve(
        PersonalRequisition $requisition,
        string $action,
        ?string $comment = null,
        ?User $actor = null,
    ): PersonalRequisition {
        if ($requisition->status !== PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA) {
            throw new InvalidArgumentException('Esta requisicion ya fue gestionada.');
        }

        if (! in_array($action, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException('Accion de resolucion no valida.');
        }

        $oldStatus = $requisition->status;
        $newStatus = $action === 'approve'
            ? PersonalRequisition::STATUS_SOLICITADA
            : PersonalRequisition::STATUS_CANCELADA;

        $comment = trim((string) $comment);
        if ($action === 'reject' && $comment === '') {
            throw new InvalidArgumentException('El comentario es obligatorio al rechazar.');
        }

        if ($comment === '') {
            $comment = $action === 'approve'
                ? ($actor === null ? 'Autorizada por gerencia (correo).' : 'Autorizada por gerencia.')
                : ($actor === null ? 'Rechazada por gerencia (correo).' : 'Rechazada por gerencia.');
        } elseif ($actor === null) {
            $prefix = $action === 'approve'
                ? 'Autorizada por gerencia (correo).'
                : 'Rechazada por gerencia (correo).';
            $comment = $prefix.' '.$comment;
        }

        $logUserId = $actor?->id ?? $this->resolveEmailApprovalLogUserId();

        DB::transaction(function () use ($requisition, $newStatus, $comment, $logUserId): void {
            $requisition->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
                'closed_at' => $newStatus === PersonalRequisition::STATUS_CANCELADA ? now() : null,
            ]);

            $requisition->statusLogs()->create([
                'from_status' => PersonalRequisition::STATUS_PENDIENTE_AUTORIZACION_GERENCIA,
                'to_status' => $newStatus,
                'changed_by' => $logUserId,
                'comment' => $comment,
            ]);
        });

        if ($newStatus === PersonalRequisition::STATUS_CANCELADA) {
            try {
                $requisition->loadMissing('requester');
                if (filled($requisition->requester?->email)) {
                    Mail::to($requisition->requester)->send(
                        new PersonalRequisitionStatusChangedMail($requisition->fresh(), $oldStatus, $newStatus, $comment)
                    );
                }
            } catch (\Exception $exception) {
                Log::error('Error enviando correo de rechazo gerencia: '.$exception->getMessage());
            }
        }

        return $requisition->fresh();
    }

    public function resolveEmailApprovalLogUserId(): int
    {
        $configured = config('requisitions.email_approval_log_user_id');
        if (filled($configured)) {
            return (int) $configured;
        }

        $permission = Permission::findByName('requisitions.approve.management', 'web');
        $approver = User::query()
            ->where('is_active', true)
            ->whereHas('permissions', fn (Builder $query) => $query->where('permissions.id', $permission->id))
            ->orderBy('id')
            ->value('id');

        if ($approver !== null) {
            return (int) $approver;
        }

        $fallback = User::query()->where('is_active', true)->orderBy('id')->value('id');

        if ($fallback === null) {
            throw new InvalidArgumentException('No hay usuario disponible para registrar la aprobacion por correo.');
        }

        return (int) $fallback;
    }
}
