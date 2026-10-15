<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeAcreditacionPending;
use App\Models\EmployeeCursoPending;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use Illuminate\Support\Facades\DB;

/**
 * Al marcar Contratado: asegura perfil mínimo en Pendientes y encola cursos/acreditación
 * sin mover a En ficha ni abrir periodo laboral.
 */
class EmployeeFichaOnHiredBootstrap
{
    public function __construct(
        private readonly EmployeeFichaProfilePrefill $prefill,
        private readonly EmployeeCursoPendingService $cursoPendingService,
        private readonly EmployeeAcreditacionPendingService $acreditacionPendingService,
        private readonly CursosAuditLogService $cursosAuditLogService,
        private readonly AcreditacionesAuditLogService $acreditacionesAuditLogService,
    ) {}

    public function bootstrap(PersonalRequisitionFichaEntry $entry, ?int $userId = null): EmployeeFichaProfile
    {
        $entry->loadMissing([
            'requisition.position',
            'requisition.city',
            'requisition.contractType',
            'requisition.client',
            'profile',
        ]);

        $profile = $entry->profile;

        if ($profile === null) {
            $profile = $this->prefill->prefillForEntry($entry);
            $profile->fill([
                'requires_courses' => true,
                'requires_acreditacion' => true,
                'requires_psicofisicos' => true,
                'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            ]);
            $profile->save();
        } else {
            $this->syncIdentityFromEntry($entry, $profile);

            // Reingreso a Pendientes: reactivar sin tocar nómina ya capturada.
            if ($entry->moved_to_ficha_at === null
                && $profile->employment_status === EmployeeFichaProfile::STATUS_DESVINCULADO) {
                $profile->employment_status = EmployeeFichaProfile::STATUS_ACTIVO;
                $profile->termination_date = null;
            }

            $profile->save();
        }

        $entry->setRelation('profile', $profile);

        $this->enqueueQueues($entry, $profile, $userId);

        return $profile->fresh() ?? $profile;
    }

    /**
     * Limpia colas abiertas y el perfil huérfano antes de borrar una entry aún pendiente.
     * Usa delete (no omit) para no bloquear un futuro re-encolado de la misma cédula.
     */
    public function cleanupBeforePendingEntryDelete(PersonalRequisitionFichaEntry $entry, ?int $userId = null): void
    {
        $documentNumber = trim((string) $entry->hired_document);

        DB::transaction(function () use ($entry, $documentNumber, $userId): void {
            if ($documentNumber !== '') {
                $this->discardOpenCursoPendings($documentNumber, $userId);
                $this->discardOpenAcreditacionPendings($documentNumber, $userId);
            }

            $profile = $entry->profile;
            if ($profile === null) {
                $profile = EmployeeFichaProfile::query()
                    ->where('personal_requisition_ficha_entry_id', $entry->id)
                    ->first();
            }

            // nullOnDelete dejaría perfil con document_number único; hay que borrarlo.
            if ($profile !== null && $entry->moved_to_ficha_at === null) {
                $profile->delete();
            }
        });
    }

    private function syncIdentityFromEntry(PersonalRequisitionFichaEntry $entry, EmployeeFichaProfile $profile): void
    {
        $attributes = $this->prefill->attributesForEntry($entry);

        // Identidad: la entry recién sincronizada es la fuente de verdad.
        $profile->fill([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
            'first_surname' => $entry->first_surname ?: ($attributes['first_surname'] ?? $profile->first_surname),
            'second_surname' => $entry->second_surname ?: ($attributes['second_surname'] ?? $profile->second_surname),
            'first_name' => $entry->first_name ?: ($attributes['first_name'] ?? $profile->first_name),
            'second_name' => $entry->second_name ?: ($attributes['second_name'] ?? $profile->second_name),
        ]);

        // Fecha de nacimiento: la RQ es fuente de verdad cuando viene informada.
        if (filled($attributes['birth_date'] ?? null)) {
            $profile->birth_date = $attributes['birth_date'];
        }

        // Solo completar gaps de prefill RQ (no pisar nómina ya guardada).
        foreach (['sex', 'salary', 'hire_date', 'position_code', 'position_name', 'work_city_code', 'work_city_name', 'document_type'] as $field) {
            if (! filled($profile->{$field}) && array_key_exists($field, $attributes) && filled($attributes[$field] ?? null)) {
                $profile->{$field} = $attributes[$field];
            }
        }
    }

    private function enqueueQueues(
        PersonalRequisitionFichaEntry $entry,
        EmployeeFichaProfile $profile,
        ?int $userId,
    ): void {
        $payload = [
            'document_number' => (string) $profile->document_number,
            'full_name' => $profile->full_name,
            'employee_ficha_profile_id' => $profile->id,
            'personal_requisition_ficha_entry_id' => $entry->id,
            'enqueued_by' => $userId,
        ];

        if ($profile->requires_courses) {
            $this->cursoPendingService->enqueueIfEligible($payload);
        }

        if ($profile->requires_acreditacion) {
            $this->acreditacionPendingService->enqueueIfEligible($payload);
        }
    }

    private function discardOpenCursoPendings(string $documentNumber, ?int $userId): void
    {
        $pendings = EmployeeCursoPending::query()
            ->forDocumentNumber($documentNumber)
            ->pending()
            ->lockForUpdate()
            ->get();

        foreach ($pendings as $pending) {
            $pendingId = $pending->id;
            $pending->delete();

            $this->cursosAuditLogService->logEvent(
                eventType: 'employee_curso_pending',
                action: 'discard_hired_rollback',
                metadata: [
                    'employee_curso_pending_id' => $pendingId,
                    'document_number' => $documentNumber,
                    'reason' => 'requisicion_ya_no_contratado',
                ],
                userId: $userId,
            );
        }
    }

    private function discardOpenAcreditacionPendings(string $documentNumber, ?int $userId): void
    {
        $pendings = EmployeeAcreditacionPending::query()
            ->forDocumentNumber($documentNumber)
            ->pending()
            ->lockForUpdate()
            ->get();

        foreach ($pendings as $pending) {
            $pendingId = $pending->id;
            $pending->delete();

            $this->acreditacionesAuditLogService->logEvent(
                eventType: 'employee_acreditacion_pending',
                action: 'discard_hired_rollback',
                metadata: [
                    'employee_acreditacion_pending_id' => $pendingId,
                    'document_number' => $documentNumber,
                    'reason' => 'requisicion_ya_no_contratado',
                ],
                userId: $userId,
            );
        }
    }
}
