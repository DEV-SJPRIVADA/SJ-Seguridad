<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeCurso;
use App\Models\EmployeeCursoPending;
use App\Models\EmployeeFichaProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeCursoPendingService
{
    public function __construct(
        private readonly CursosAuditLogService $auditLogService,
    ) {}

    /**
     * Encola si es elegible (uso futuro T2 / hooks Ficha). Idempotente.
     *
     * @param  array{
     *     document_number: string,
     *     full_name?: string|null,
     *     employee_ficha_profile_id?: int|null,
     *     personal_requisition_ficha_entry_id?: int|null,
     *     enqueued_by?: int|null,
     * }  $data
     */
    public function enqueueIfEligible(array $data): ?EmployeeCursoPending
    {
        $documentNumber = trim((string) ($data['document_number'] ?? ''));

        if ($documentNumber === '') {
            return null;
        }

        return DB::transaction(function () use ($documentNumber, $data): ?EmployeeCursoPending {
            if ($this->hasCursoHistory($documentNumber)) {
                return null;
            }

            $existingClosed = EmployeeCursoPending::query()
                ->forDocumentNumber($documentNumber)
                ->whereIn('status', [
                    EmployeeCursoPending::STATUS_OMITTED,
                    EmployeeCursoPending::STATUS_RESOLVED,
                ])
                ->lockForUpdate()
                ->exists();

            if ($existingClosed) {
                return null;
            }

            $existingPending = EmployeeCursoPending::query()
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

            $pending = EmployeeCursoPending::query()->create([
                'document_number' => $documentNumber,
                'full_name' => $fullName !== '' ? $fullName : null,
                'employee_ficha_profile_id' => $profile?->id
                    ?? (($profileId !== null && $profileId > 0) ? $profileId : null),
                'personal_requisition_ficha_entry_id' => isset($data['personal_requisition_ficha_entry_id'])
                    ? (int) $data['personal_requisition_ficha_entry_id']
                    : ($profile?->personal_requisition_ficha_entry_id),
                'status' => EmployeeCursoPending::STATUS_PENDING,
                'enqueued_at' => now(),
                'enqueued_by' => $data['enqueued_by'] ?? null,
            ]);

            $this->auditLogService->logEvent(
                eventType: 'employee_curso_pending',
                action: 'enqueue',
                metadata: [
                    'employee_curso_pending_id' => $pending->id,
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
        ?EmployeeCurso $curso = null,
        ?int $userId = null,
    ): ?EmployeeCursoPending {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return null;
        }

        return DB::transaction(function () use ($documentNumber, $curso, $userId): ?EmployeeCursoPending {
            $pending = EmployeeCursoPending::query()
                ->forDocumentNumber($documentNumber)
                ->pending()
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                return null;
            }

            $pending->update([
                'status' => EmployeeCursoPending::STATUS_RESOLVED,
                'resolved_at' => now(),
                'resolved_by' => $userId,
                'resolved_via' => EmployeeCursoPending::RESOLVED_VIA_FIRST_CURSO,
                'employee_curso_id' => $curso?->id,
            ]);

            $this->auditLogService->logEvent(
                eventType: 'employee_curso_pending',
                action: 'resolve',
                metadata: [
                    'employee_curso_pending_id' => $pending->id,
                    'document_number' => $pending->document_number,
                    'employee_curso_id' => $curso?->id,
                    'resolved_via' => EmployeeCursoPending::RESOLVED_VIA_FIRST_CURSO,
                ],
                model: $pending->fresh(),
                userId: $userId,
            );

            return $pending->fresh();
        });
    }

    public function omit(
        EmployeeCursoPending $pending,
        ?string $reason = null,
        ?int $userId = null,
    ): EmployeeCursoPending {
        if ($pending->status !== EmployeeCursoPending::STATUS_PENDING) {
            return $pending;
        }

        return DB::transaction(function () use ($pending, $reason, $userId): EmployeeCursoPending {
            $locked = EmployeeCursoPending::query()
                ->whereKey($pending->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== EmployeeCursoPending::STATUS_PENDING) {
                return $locked;
            }

            $omitReason = $reason !== null ? trim($reason) : null;
            if ($omitReason === '') {
                $omitReason = null;
            }

            $locked->update([
                'status' => EmployeeCursoPending::STATUS_OMITTED,
                'omitted_at' => now(),
                'omitted_by' => $userId,
                'omit_reason' => $omitReason,
            ]);

            $this->auditLogService->logEvent(
                eventType: 'employee_curso_pending',
                action: 'omit',
                reason: $omitReason,
                metadata: [
                    'employee_curso_pending_id' => $locked->id,
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
        return EmployeeCursoPending::query()
            ->pendingWithActiveProfile()
            ->count();
    }

    /**
     * @return Collection<int, EmployeeCursoPending>
     */
    public function listPendingActivos(): Collection
    {
        return EmployeeCursoPending::query()
            ->pendingWithActiveProfile()
            ->with(['employeeFichaProfile:id,document_number,full_name,employment_status'])
            ->orderByDesc('enqueued_at')
            ->orderByDesc('id')
            ->get();
    }

    public function hasCursoHistory(string $documentNumber): bool
    {
        return EmployeeCurso::query()
            ->forDocumentNumber(trim($documentNumber))
            ->exists();
    }
}
