<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\EmployeeAcreditacionPending;
use App\Models\EmployeeFichaProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeAcreditacionPendingService
{
    public function __construct(
        private readonly AcreditacionesAuditLogService $auditLogService,
    ) {}

    /**
     * @param  array{
     *     document_number: string,
     *     full_name?: string|null,
     *     employee_ficha_profile_id?: int|null,
     *     personal_requisition_ficha_entry_id?: int|null,
     *     enqueued_by?: int|null,
     * }  $data
     */
    public function enqueueIfEligible(array $data): ?EmployeeAcreditacionPending
    {
        $documentNumber = trim((string) ($data['document_number'] ?? ''));

        if ($documentNumber === '') {
            return null;
        }

        return DB::transaction(function () use ($documentNumber, $data): ?EmployeeAcreditacionPending {
            if ($this->hasAcreditacionHistory($documentNumber)) {
                return null;
            }

            $existingClosed = EmployeeAcreditacionPending::query()
                ->forDocumentNumber($documentNumber)
                ->whereIn('status', [
                    EmployeeAcreditacionPending::STATUS_OMITTED,
                    EmployeeAcreditacionPending::STATUS_RESOLVED,
                ])
                ->lockForUpdate()
                ->exists();

            if ($existingClosed) {
                return null;
            }

            $existingPending = EmployeeAcreditacionPending::query()
                ->forDocumentNumber($documentNumber)
                ->pending()
                ->lockForUpdate()
                ->first();

            if ($existingPending !== null) {
                return $existingPending;
            }

            $profileId = isset($data['employee_ficha_profile_id'])
                ? (int) $data['employee_ficha_profile_id']
                : null;

            $profile = null;
            if ($profileId !== null && $profileId > 0) {
                $profile = EmployeeFichaProfile::query()->find($profileId);
            }

            if ($profile === null) {
                $profile = EmployeeFichaProfile::query()
                    ->where('document_number', $documentNumber)
                    ->first();
            }

            if ($profile !== null && $profile->employment_status !== EmployeeFichaProfile::STATUS_ACTIVO) {
                return null;
            }

            $fullName = trim((string) ($data['full_name'] ?? ''));
            if ($fullName === '' && $profile !== null) {
                $fullName = (string) ($profile->full_name ?? '');
            }

            $pending = EmployeeAcreditacionPending::query()->create([
                'document_number' => $documentNumber,
                'full_name' => $fullName !== '' ? $fullName : null,
                'employee_ficha_profile_id' => $profile?->id
                    ?? (($profileId !== null && $profileId > 0) ? $profileId : null),
                'personal_requisition_ficha_entry_id' => isset($data['personal_requisition_ficha_entry_id'])
                    ? (int) $data['personal_requisition_ficha_entry_id']
                    : ($profile?->personal_requisition_ficha_entry_id),
                'status' => EmployeeAcreditacionPending::STATUS_PENDING,
                'enqueued_at' => now(),
                'enqueued_by' => $data['enqueued_by'] ?? null,
            ]);

            $this->auditLogService->logEvent(
                eventType: 'employee_acreditacion_pending',
                action: 'enqueue',
                metadata: [
                    'employee_acreditacion_pending_id' => $pending->id,
                    'document_number' => $pending->document_number,
                ],
                model: $pending,
                userId: isset($data['enqueued_by']) ? (int) $data['enqueued_by'] : null,
            );

            return $pending;
        });
    }

    public function resolveByDocument(
        string $documentNumber,
        ?AcreditacionAcreditado $acreditado = null,
        ?int $userId = null,
    ): ?EmployeeAcreditacionPending {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return null;
        }

        return DB::transaction(function () use ($documentNumber, $acreditado, $userId): ?EmployeeAcreditacionPending {
            $pending = EmployeeAcreditacionPending::query()
                ->forDocumentNumber($documentNumber)
                ->pending()
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                return null;
            }

            $pending->update([
                'status' => EmployeeAcreditacionPending::STATUS_RESOLVED,
                'resolved_at' => now(),
                'resolved_by' => $userId,
                'resolved_via' => EmployeeAcreditacionPending::RESOLVED_VIA_FIRST_ACREDITADO,
                'acreditacion_acreditado_id' => $acreditado?->id,
            ]);

            $this->auditLogService->logEvent(
                eventType: 'employee_acreditacion_pending',
                action: 'resolve',
                metadata: [
                    'employee_acreditacion_pending_id' => $pending->id,
                    'document_number' => $pending->document_number,
                    'acreditacion_acreditado_id' => $acreditado?->id,
                    'resolved_via' => EmployeeAcreditacionPending::RESOLVED_VIA_FIRST_ACREDITADO,
                ],
                model: $pending->fresh(),
                userId: $userId,
            );

            return $pending->fresh();
        });
    }

    public function omit(
        EmployeeAcreditacionPending $pending,
        ?string $reason = null,
        ?int $userId = null,
    ): EmployeeAcreditacionPending {
        if ($pending->status !== EmployeeAcreditacionPending::STATUS_PENDING) {
            return $pending;
        }

        return DB::transaction(function () use ($pending, $reason, $userId): EmployeeAcreditacionPending {
            $locked = EmployeeAcreditacionPending::query()
                ->whereKey($pending->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== EmployeeAcreditacionPending::STATUS_PENDING) {
                return $locked;
            }

            $omitReason = $reason !== null ? trim($reason) : null;
            if ($omitReason === '') {
                $omitReason = null;
            }

            $locked->update([
                'status' => EmployeeAcreditacionPending::STATUS_OMITTED,
                'omitted_at' => now(),
                'omitted_by' => $userId,
                'omit_reason' => $omitReason,
            ]);

            EmployeeFichaProfile::query()
                ->where('document_number', $locked->document_number)
                ->update(['requires_acreditacion' => false]);

            $this->auditLogService->logEvent(
                eventType: 'employee_acreditacion_pending',
                action: 'omit',
                reason: $omitReason,
                metadata: [
                    'employee_acreditacion_pending_id' => $locked->id,
                    'document_number' => $locked->document_number,
                ],
                model: $locked->fresh(),
                userId: $userId,
            );

            return $locked->fresh();
        });
    }

    public function countPendingActivos(): int
    {
        return EmployeeAcreditacionPending::query()
            ->pendingWithActiveProfile()
            ->count();
    }

    /**
     * @return Collection<int, EmployeeAcreditacionPending>
     */
    public function listPendingActivos(): Collection
    {
        return EmployeeAcreditacionPending::query()
            ->pendingWithActiveProfile()
            ->with(['employeeFichaProfile:id,document_number,full_name,employment_status'])
            ->orderByDesc('enqueued_at')
            ->orderByDesc('id')
            ->get();
    }

    public function hasAcreditacionHistory(string $documentNumber): bool
    {
        return AcreditacionAcreditado::query()
            ->where('document_number', trim($documentNumber))
            ->exists();
    }
}
