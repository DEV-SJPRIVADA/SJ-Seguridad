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

            $profile = $entry->profile ?? new EmployeeFichaProfile([
                'personal_requisition_ficha_entry_id' => $entry->id,
            ]);

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

        if ($entry->isRehirePending()) {
            throw ValidationException::withMessages([
                'ficha_entry' => 'Los reingresos usan Gestionar reingreso. La carta rápida aplica a contrataciones nuevas en Pendientes.',
            ]);
        }
    }
}
