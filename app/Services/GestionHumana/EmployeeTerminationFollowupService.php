<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeTerminationFollowup;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EmployeeTerminationFollowupService
{
    public function __construct(
        private readonly DesvinculacionesAuditLogService $auditLogService,
        private readonly EmployeeFichaEmploymentPeriodService $periodService,
    ) {}

    /**
     * Crea seguimiento para un periodo cerrado si no existe.
     * Si ya existe y $letterGenerated es true, actualiza el flag.
     */
    public function ensureForClosedPeriod(
        EmployeeFichaEmploymentPeriod $period,
        PersonalRequisitionFichaEntry $entry,
        ?int $userId = null,
        bool $letterGenerated = false,
    ): EmployeeTerminationFollowup {
        $existing = EmployeeTerminationFollowup::query()
            ->where('employee_ficha_employment_period_id', $period->id)
            ->first();

        if ($existing !== null) {
            if ($letterGenerated && ! $existing->letter_generated) {
                $existing->letter_generated = true;
                $existing->save();
            }

            return $existing->fresh();
        }

        $entry->loadMissing('profile');

        return EmployeeTerminationFollowup::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'employee_ficha_employment_period_id' => $period->id,
            'document_number' => (string) ($entry->hired_document ?: $entry->profile?->document_number),
            'full_name' => (string) ($entry->hired_full_name ?: $entry->profile?->full_name),
            'position_name' => $period->position_name,
            'termination_cause_code' => $period->termination_cause_code,
            'termination_cause_name' => $period->termination_cause_name,
            'is_rehireable' => $period->is_rehireable,
            'termination_notes' => $period->termination_notes,
            'termination_date' => $period->termination_date ?? $period->last_work_day,
            'registered_at' => now(),
            'letter_generated' => $letterGenerated,
            'created_by' => $userId,
        ]);
    }

    public function markLetterGenerated(
        EmployeeFichaEmploymentPeriod $period,
        PersonalRequisitionFichaEntry $entry,
        ?int $userId = null,
    ): EmployeeTerminationFollowup {
        return $this->ensureForClosedPeriod(
            $period,
            $entry,
            $userId,
            letterGenerated: true,
        );
    }

    /**
     * Revierte la desvinculacion: reabre periodo, perfil activo, borra carta y elimina seguimiento.
     */
    public function revert(
        EmployeeTerminationFollowup $followup,
        User $user,
        string $reason,
    ): void {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'El motivo de la reversion es obligatorio.',
            ]);
        }

        $followup->loadMissing(['employmentPeriod', 'fichaEntry.profile']);

        $period = $followup->employmentPeriod;
        $entry = $followup->fichaEntry;

        if ($period === null || $entry === null) {
            throw ValidationException::withMessages([
                'followup' => 'El seguimiento no tiene periodo o ficha asociados.',
            ]);
        }

        $letterPath = filled($period->termination_letter_path)
            ? (string) $period->termination_letter_path
            : null;

        $metadata = [
            'followup_id' => $followup->id,
            'period_id' => $period->id,
            'document_number' => $followup->document_number,
            'full_name' => $followup->full_name,
            'letter_deleted' => $letterPath !== null,
        ];

        DB::transaction(function () use ($followup, $period, $user, $reason, $metadata): void {
            $this->periodService->reopenClosedPeriod($period);

            $followup->delete();

            $this->auditLogService->logEvent(
                eventType: 'termination_followup',
                action: 'revert',
                reason: $reason,
                metadata: $metadata,
                model: $period->fresh(),
                userId: $user->id,
            );
        });

        if ($letterPath !== null && Storage::disk('local')->exists($letterPath)) {
            Storage::disk('local')->delete($letterPath);
        }
    }

    /**
     * PATCH parcial: solo checks y/o payroll_delivered_at.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updatePartial(
        EmployeeTerminationFollowup $followup,
        array $payload,
        ?User $user = null,
    ): EmployeeTerminationFollowup {
        $allowed = array_merge(EmployeeTerminationFollowup::CHECK_FIELDS, ['payroll_delivered_at']);
        $changes = collect($payload)->only($allowed);

        if ($changes->isEmpty()) {
            return $followup;
        }

        $before = [];
        $after = [];

        foreach ($changes as $field => $value) {
            $before[$field] = $this->snapshotEditableValue($followup, $field);
            $followup->{$field} = $value;
            $after[$field] = $this->snapshotEditableValue($followup, $field);
        }

        if ($before === $after) {
            return $followup;
        }

        $followup->save();

        $this->auditLogService->logModelChange(
            eventType: 'termination_followup',
            action: 'update',
            model: $followup,
            before: $before,
            after: $after,
            metadata: [
                'followup_id' => $followup->id,
                'document_number' => $followup->document_number,
            ],
            userId: $user?->id,
        );

        return $followup->fresh();
    }

    private function snapshotEditableValue(EmployeeTerminationFollowup $followup, string $field): mixed
    {
        if ($field === 'payroll_delivered_at') {
            return $followup->payroll_delivered_at?->toDateString();
        }

        return (bool) $followup->{$field};
    }
}
