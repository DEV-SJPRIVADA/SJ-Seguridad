<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class EmployeeFichaEmploymentPeriodService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function openPeriod(
        PersonalRequisitionFichaEntry $entry,
        array $attributes,
        int $userId,
        ?int $requisitionId = null,
    ): EmployeeFichaEmploymentPeriod {
        if ($this->activePeriod($entry) !== null) {
            throw ValidationException::withMessages([
                'employment_period' => 'El empleado ya tiene un vinculo laboral activo.',
            ]);
        }

        $sequence = (int) EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->max('sequence') + 1;

        $period = EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'personal_requisition_id' => $requisitionId ?? $entry->personal_requisition_id,
            'sequence' => max(1, $sequence),
            'status' => EmployeeFichaEmploymentPeriod::STATUS_ACTIVO,
            'opened_by' => $userId,
            ...$this->periodAttributesFromProfileData($attributes),
        ]);

        $this->syncCatalogNamesOnPeriod($period);

        return $period->fresh();
    }

    /**
     * @param  array<string, mixed>  $terminationData
     */
    public function closeActivePeriod(
        PersonalRequisitionFichaEntry $entry,
        array $terminationData,
        int $userId,
    ): EmployeeFichaEmploymentPeriod {
        $period = $this->activePeriod($entry);

        if ($period === null) {
            throw ValidationException::withMessages([
                'employment_period' => 'No hay un vinculo laboral activo para desvincular.',
            ]);
        }

        $causeCode = trim((string) ($terminationData['termination_cause_code'] ?? ''));
        $causeName = PayrollCatalogItem::query()
            ->ofType('termination_cause')
            ->where('code', $causeCode)
            ->value('name');

        $period->fill([
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'termination_cause_code' => $causeCode !== '' ? $causeCode : null,
            'termination_cause_name' => $causeName,
            'is_rehireable' => (bool) ($terminationData['is_rehireable'] ?? false),
            'last_work_day' => $terminationData['last_work_day'] ?? null,
            'termination_date' => $terminationData['termination_date'] ?? null,
            'termination_notes' => $terminationData['termination_notes'] ?? null,
            'closed_by' => $userId,
        ]);
        $period->save();

        return $period->fresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function syncActivePeriodFromProfileAttributes(
        PersonalRequisitionFichaEntry $entry,
        array $attributes,
        int $userId,
    ): ?EmployeeFichaEmploymentPeriod {
        $period = $this->activePeriod($entry);

        if ($period === null) {
            if ($entry->moved_to_ficha_at === null) {
                return null;
            }

            return $this->openPeriod($entry, $attributes, $userId);
        }

        $period->fill($this->periodAttributesFromProfileData($attributes));
        $period->save();
        $this->syncCatalogNamesOnPeriod($period);

        return $period->fresh();
    }

    public function syncProfileFromActivePeriod(PersonalRequisitionFichaEntry $entry, ?EmployeeFichaProfile $profile = null): EmployeeFichaProfile
    {
        $profile ??= $entry->profile ?? new EmployeeFichaProfile([
            'personal_requisition_ficha_entry_id' => $entry->id,
        ]);

        $period = $this->activePeriod($entry);

        if ($period !== null) {
            $profile->fill($period->profileSnapshotAttributes());

            return $profile;
        }

        $latestClosed = $this->latestClosedPeriod($entry);

        if ($latestClosed !== null) {
            $profile->fill($latestClosed->profileSnapshotAttributes());

            return $profile;
        }

        $profile->employment_status = EmployeeFichaProfile::STATUS_ACTIVO;
        $profile->termination_date = null;

        return $profile;
    }

    public function syncProfileAfterTermination(PersonalRequisitionFichaEntry $entry): EmployeeFichaProfile
    {
        $profile = $entry->profile ?? new EmployeeFichaProfile([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $entry->hired_full_name,
        ]);

        $latestClosed = $this->latestClosedPeriod($entry);

        if ($latestClosed !== null) {
            $profile->fill($latestClosed->profileSnapshotAttributes());
        } else {
            $profile->employment_status = EmployeeFichaProfile::STATUS_DESVINCULADO;
        }

        $profile->save();

        return $profile->fresh();
    }

    public function activePeriod(PersonalRequisitionFichaEntry $entry): ?EmployeeFichaEmploymentPeriod
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->active()
            ->first();
    }

    public function latestClosedPeriod(PersonalRequisitionFichaEntry $entry): ?EmployeeFichaEmploymentPeriod
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->closed()
            ->orderByDesc('sequence')
            ->first();
    }

    public function isRehireable(PersonalRequisitionFichaEntry $entry): bool
    {
        if ($this->activePeriod($entry) !== null) {
            return false;
        }

        $latestClosed = $this->latestClosedPeriod($entry);

        return $latestClosed !== null && $latestClosed->is_rehireable === true;
    }

    public function isEligibleForRehire(PersonalRequisitionFichaEntry $entry): bool
    {
        return $entry->moved_to_ficha_at !== null
            && $entry->profile?->employment_status === EmployeeFichaProfile::STATUS_DESVINCULADO
            && $this->isRehireable($entry);
    }

    /**
     * @return Collection<int, EmployeeFichaEmploymentPeriod>
     */
    public function historyForEntry(PersonalRequisitionFichaEntry $entry)
    {
        return EmployeeFichaEmploymentPeriod::query()
            ->where('personal_requisition_ficha_entry_id', $entry->id)
            ->with(['requisition:id,code', 'openedBy:id,name', 'closedBy:id,name'])
            ->orderByDesc('sequence')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function periodAttributesFromProfile(EmployeeFichaProfile $profile): array
    {
        return $this->periodAttributesFromProfileData($profile->getAttributes());
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function periodAttributesFromProfileData(array $attributes): array
    {
        return [
            'hire_date' => $attributes['hire_date'] ?? null,
            'salary' => $attributes['salary'] ?? null,
            'position_code' => $attributes['position_code'] ?? null,
            'position_name' => $attributes['position_name'] ?? null,
            'cost_center_code' => $attributes['cost_center_code'] ?? null,
            'cost_center_name' => $attributes['cost_center_name'] ?? null,
            'contract_type_code' => $attributes['contract_type_code'] ?? null,
            'contract_type_name' => $attributes['contract_type_name'] ?? null,
            'contract_end_date' => $attributes['contract_end_date'] ?? null,
            'work_center_name' => $attributes['work_center_name'] ?? null,
            'eps_code' => $attributes['eps_code'] ?? null,
            'eps_name' => $attributes['eps_name'] ?? null,
            'afp_code' => $attributes['afp_code'] ?? null,
            'afp_name' => $attributes['afp_name'] ?? null,
            'linkage_type' => $attributes['linkage_type'] ?? null,
        ];
    }

    private function syncCatalogNamesOnPeriod(EmployeeFichaEmploymentPeriod $period): void
    {
        $map = [
            'eps_code' => ['eps', 'eps_name'],
            'afp_code' => ['afp', 'afp_name'],
            'position_code' => ['position', 'position_name'],
            'cost_center_code' => ['cost_center', 'cost_center_name'],
        ];

        $dirty = false;

        foreach ($map as $codeField => [$catalogType, $nameField]) {
            $code = $period->{$codeField};

            if ($code === null || $code === '') {
                continue;
            }

            $name = PayrollCatalogItem::query()
                ->ofType($catalogType)
                ->where('code', $code)
                ->value('name');

            if ($name !== null && $period->{$nameField} !== $name) {
                $period->{$nameField} = $name;
                $dirty = true;
            }
        }

        if ($dirty) {
            $period->save();
        }
    }
}
