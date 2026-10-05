<?php

namespace App\Services\GestionHumana\ContratacionLetter;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;
use App\Services\GestionHumana\EmployeeFichaNameParser;
use App\Services\GestionHumana\EmployeeFichaProfileCatalogSync;
use App\Services\GestionHumana\EmployeeFichaProfilePrefill;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Carta de contratación desde Pendientes: guarda solo datos mínimos en perfil/periodo
 * sin mover a En ficha (moved_to_ficha_at permanece null).
 */
class ContratacionQuickLetterService
{
    public function __construct(
        private readonly EmployeeFichaProfilePrefill $profilePrefill,
        private readonly EmployeeFichaProfileCatalogSync $profileCatalogSync,
        private readonly EmployeeFichaEmploymentPeriodService $periodService,
        private readonly ContratacionLetterPackGeneratorService $packGenerator,
    ) {}

    /**
     * Perfil precargado (persistido o en memoria) para el formulario de carta rápida.
     */
    public function profileForForm(PersonalRequisitionFichaEntry $entry): EmployeeFichaProfile
    {
        $this->assertEligiblePending($entry);

        return $this->profilePrefill->buildForEntry($entry);
    }

    /**
     * @param  array{
     *     full_name: string,
     *     document_number: string,
     *     birth_place: string,
     *     address: string,
     *     residence_city_code: string,
     *     phone: string,
     *     email: string,
     *     birth_date: string,
     *     salary: float|int|string,
     *     hire_date: string,
     *     position_name: string,
     *     position_code?: string|null,
     *     template_ids: list<int>,
     *     signatory_id: int
     * }  $data
     * @return array{
     *     storage_path: string,
     *     download_name: string,
     *     document_count: int,
     *     output_type: string,
     *     template_ids: list<int>,
     *     period: EmployeeFichaEmploymentPeriod,
     *     entry: PersonalRequisitionFichaEntry
     * }
     */
    public function saveAndGenerate(
        PersonalRequisitionFichaEntry $entry,
        array $data,
        int $userId,
    ): array {
        $this->assertEligiblePending($entry);

        /** @var array{period: EmployeeFichaEmploymentPeriod, entry: PersonalRequisitionFichaEntry} $prepared */
        $prepared = DB::transaction(function () use ($entry, $data, $userId): array {
            $entry = PersonalRequisitionFichaEntry::query()
                ->pending()
                ->with(['requisition.city', 'requisition.position', 'profile'])
                ->lockForUpdate()
                ->findOrFail($entry->id);

            $this->assertEligiblePending($entry);

            $parsedName = EmployeeFichaNameParser::parse((string) $data['full_name']);
            $document = preg_replace('/\D+/', '', (string) $data['document_number']) ?: (string) $data['document_number'];

            $entry->update([
                'hired_document' => $document,
                'hired_full_name' => $parsedName['full_name'],
                'first_surname' => $parsedName['first_surname'],
                'second_surname' => $parsedName['second_surname'],
                'first_name' => $parsedName['first_name'],
                'second_name' => $parsedName['second_name'],
            ]);

            $profile = $this->resolveProfileForDocument($entry, $document);

            $profile->fill([
                'document_number' => $document,
                'document_type' => $profile->document_type ?: 'C',
                'full_name' => $parsedName['full_name'],
                'first_surname' => $parsedName['first_surname'],
                'second_surname' => $parsedName['second_surname'],
                'first_name' => $parsedName['first_name'],
                'second_name' => $parsedName['second_name'],
                'birth_place' => $data['birth_place'],
                'address' => $data['address'],
                'residence_city_code' => $data['residence_city_code'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'birth_date' => $data['birth_date'],
                'salary' => $data['salary'],
                'hire_date' => $data['hire_date'],
                'position_name' => $data['position_name'],
                'position_code' => $data['position_code'] ?? $profile->position_code,
                'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
                // Reingreso: limpia retiro del vínculo anterior al preparar la carta.
                'termination_date' => null,
            ]);

            $this->profileCatalogSync->sync($profile);
            $profile->save();

            $period = $this->periodService->openOrSyncPeriodForQuickLetter(
                $entry->fresh(['profile']),
                $profile->getAttributes(),
                $userId,
            );

            return [
                'period' => $period,
                'entry' => $entry->fresh(['profile', 'requisition.city', 'requisition.position']),
            ];
        });

        $result = $this->packGenerator->generate(
            $prepared['period'],
            $prepared['entry'],
            $data['template_ids'],
            $data['signatory_id'],
        );

        return array_merge($result, [
            'period' => $prepared['period']->fresh(),
            'entry' => $prepared['entry'],
        ]);
    }

    public function assertEligiblePending(PersonalRequisitionFichaEntry $entry): void
    {
        if ($entry->moved_to_ficha_at !== null) {
            throw ValidationException::withMessages([
                'ficha_entry' => 'Este empleado ya está en ficha. Genere la carta desde la ficha del empleado.',
            ]);
        }
    }

    /**
     * Evita el unique de cédula cuando el pendiente nuevo no tiene perfil pero la cédula
     * ya existe en otra entrada (reingreso mal sincronizado / entrada duplicada).
     */
    private function resolveProfileForDocument(
        PersonalRequisitionFichaEntry $entry,
        string $document,
    ): EmployeeFichaProfile {
        $existingByDocument = EmployeeFichaProfile::query()
            ->where('document_number', $document)
            ->lockForUpdate()
            ->first();

        // Preferir siempre el perfil canónico de la cédula (evita unique emp_ficha_profiles_doc_uq).
        if ($existingByDocument !== null) {
            $ownerEntryId = (int) ($existingByDocument->personal_requisition_ficha_entry_id ?? 0);

            if ($ownerEntryId === (int) $entry->id) {
                return $existingByDocument;
            }

            $ownerEntry = $ownerEntryId > 0
                ? PersonalRequisitionFichaEntry::query()->lockForUpdate()->find($ownerEntryId)
                : null;

            if (
                $ownerEntry !== null
                && $ownerEntry->moved_to_ficha_at !== null
                && $existingByDocument->employment_status === EmployeeFichaProfile::STATUS_ACTIVO
            ) {
                throw ValidationException::withMessages([
                    'document_number' => 'Esta cédula ya pertenece a un empleado activo en ficha. Complete el retiro o use Gestionar Empleado en ese registro.',
                ]);
            }

            // Si este pendiente tiene otro perfil vacío/distinto, liberarlo antes de reasignar.
            if (
                $entry->profile !== null
                && (int) $entry->profile->id !== (int) $existingByDocument->id
            ) {
                $orphanProfile = $entry->profile;
                $orphanProfile->personal_requisition_ficha_entry_id = null;
                $orphanProfile->save();
            }

            $this->reattachProfileAndPeriodsToEntry($existingByDocument, $entry, $ownerEntry);
            $entry->setRelation('profile', $existingByDocument);

            return $existingByDocument;
        }

        if ($entry->profile !== null) {
            return $entry->profile;
        }

        return new EmployeeFichaProfile([
            'personal_requisition_ficha_entry_id' => $entry->id,
        ]);
    }

    private function reattachProfileAndPeriodsToEntry(
        EmployeeFichaProfile $profile,
        PersonalRequisitionFichaEntry $targetEntry,
        ?PersonalRequisitionFichaEntry $ownerEntry,
    ): void {
        $fromEntryId = (int) ($profile->personal_requisition_ficha_entry_id ?? 0);

        if ($fromEntryId > 0 && $fromEntryId !== (int) $targetEntry->id) {
            EmployeeFichaEmploymentPeriod::query()
                ->where('personal_requisition_ficha_entry_id', $fromEntryId)
                ->update([
                    'personal_requisition_ficha_entry_id' => $targetEntry->id,
                    'personal_requisition_id' => $targetEntry->personal_requisition_id,
                ]);
        }

        $profile->personal_requisition_ficha_entry_id = $targetEntry->id;
        $profile->save();

        // Entrada anterior vacía: intentar limpiarla; si hay FK inesperada, no bloquear la carta.
        if ($ownerEntry === null || $ownerEntry->id === $targetEntry->id) {
            return;
        }

        $ownerEntry->unsetRelation('profile');
        $hasProfile = EmployeeFichaProfile::query()
            ->where('personal_requisition_ficha_entry_id', $ownerEntry->id)
            ->exists();
        $hasPeriods = EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $ownerEntry->id)
            ->exists();

        if ($hasProfile || $hasPeriods) {
            return;
        }

        try {
            $ownerEntry->delete();
        } catch (Throwable) {
            // Huérfana sin perfil: el listado puede mostrar residual; la carta no debe fallar.
        }
    }
}
