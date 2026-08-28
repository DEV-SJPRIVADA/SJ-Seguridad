<?php

namespace App\Services\Requisitions;

use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Services\GestionHumana\EmployeeFichaEmploymentPeriodService;

class PersonalRequisitionFichaSync
{
    public function __construct(
        private readonly EmployeeFichaEmploymentPeriodService $employmentPeriodService,
    ) {}

    /**
     * Mantiene sincronizada la entrada 1:1 de `personal_requisition_ficha_entries`
     * con el estado y los datos de contratacion capturados en la requisicion.
     */
    public function syncOnUpdate(
        PersonalRequisition $requisition,
        string $newStatus,
        ?string $document,
        ?string $fullName,
        bool $confirmDuplicate,
        int $userId,
        ?string $firstSurname = null,
        ?string $secondSurname = null,
        ?string $firstName = null,
        ?string $secondName = null,
    ): void {
        $ownEntry = PersonalRequisitionFichaEntry::query()
            ->where('personal_requisition_id', $requisition->id)
            ->first();

        if ($newStatus !== PersonalRequisition::STATUS_CONTRATADO) {
            if ($ownEntry !== null && $ownEntry->moved_to_ficha_at === null) {
                $ownEntry->delete();
            }

            return;
        }

        $document = trim((string) $document);
        $fullName = trim((string) $fullName);
        $firstSurname = $firstSurname ? trim($firstSurname) : null;
        $secondSurname = $secondSurname ? trim($secondSurname) : null;
        $firstName = $firstName ? trim($firstName) : null;
        $secondName = $secondName ? trim($secondName) : null;

        $existingByDocument = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $document)
            ->with('profile')
            ->first();

        if ($existingByDocument !== null
            && ($ownEntry === null || $existingByDocument->id !== $ownEntry->id)
            && $existingByDocument->moved_to_ficha_at !== null
            && $existingByDocument->profile?->employment_status === EmployeeFichaProfile::STATUS_DESVINCULADO
            && $this->employmentPeriodService->isRehireable($existingByDocument)) {
            if ($ownEntry !== null && $ownEntry->id !== $existingByDocument->id) {
                $ownEntry->delete();
            }

            $existingByDocument->update([
                'personal_requisition_id' => $requisition->id,
                'first_surname' => $firstSurname ?: $existingByDocument->first_surname,
                'second_surname' => $secondSurname ?: $existingByDocument->second_surname,
                'first_name' => $firstName ?: $existingByDocument->first_name,
                'second_name' => $secondName ?: $existingByDocument->second_name,
                'hired_full_name' => $fullName,
                'moved_to_ficha_at' => null,
                'moved_to_ficha_by' => null,
            ]);

            return;
        }

        $otherEntry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', $document)
            ->where('personal_requisition_id', '!=', $requisition->id)
            ->first();

        if ($otherEntry !== null && $confirmDuplicate) {
            if ($ownEntry !== null && $ownEntry->id !== $otherEntry->id) {
                $ownEntry->delete();
            }

            $otherEntry->update([
                'personal_requisition_id' => $requisition->id,
                'first_surname' => $firstSurname ?: $otherEntry->first_surname,
                'second_surname' => $secondSurname ?: $otherEntry->second_surname,
                'first_name' => $firstName ?: $otherEntry->first_name,
                'second_name' => $secondName ?: $otherEntry->second_name,
                'hired_full_name' => $fullName,
            ]);

            return;
        }

        if ($ownEntry !== null) {
            $ownEntry->update([
                'hired_document' => $document,
                'first_surname' => $firstSurname ?: $ownEntry->first_surname,
                'second_surname' => $secondSurname ?: $ownEntry->second_surname,
                'first_name' => $firstName ?: $ownEntry->first_name,
                'second_name' => $secondName ?: $ownEntry->second_name,
                'hired_full_name' => $fullName,
            ]);

            return;
        }

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => $document,
            'first_surname' => $firstSurname,
            'second_surname' => $secondSurname,
            'first_name' => $firstName,
            'second_name' => $secondName,
            'hired_full_name' => $fullName,
            'created_by' => $userId,
        ]);
    }
}
