<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\RequisitionPositionPayrollMap;

class EmployeeFichaProfilePrefill
{
    public function prefillForEntry(PersonalRequisitionFichaEntry $entry): EmployeeFichaProfile
    {
        $entry->loadMissing(['requisition.position', 'requisition.city', 'requisition.contractType', 'requisition.client', 'profile']);

        if ($entry->profile !== null) {
            return $entry->profile;
        }

        return EmployeeFichaProfile::query()->create($this->attributesForEntry($entry));
    }

    /**
     * Construye un perfil precargado desde la requisicion sin persistirlo. Si el pendiente
     * ya tiene un perfil propio (creado previamente, por ejemplo via `/{id}/ficha`), se
     * reutiliza ese perfil real en vez de sobrescribirlo con el prefill.
     */
    public function buildForEntry(PersonalRequisitionFichaEntry $entry): EmployeeFichaProfile
    {
        $entry->loadMissing(['requisition.position', 'requisition.city', 'requisition.contractType', 'requisition.client', 'profile']);

        $prefillAttributes = $this->attributesForEntry($entry);

        if ($entry->profile !== null) {
            if ($entry->isRehirePending()) {
                return $this->mergeRehireProfile($entry->profile, $prefillAttributes);
            }

            return $this->withMissingWorkCity($entry->profile, $prefillAttributes);
        }

        return new EmployeeFichaProfile($prefillAttributes);
    }

    /**
     * Resuelve ciudad de trabajo desde requisition.city (city_id) hacia catalogo Ciudad.
     *
     * @return array{work_city_code: ?string, work_city_name: ?string}
     */
    public function workCityAttributesFromEntry(PersonalRequisitionFichaEntry $entry): array
    {
        $entry->loadMissing(['requisition.city']);
        $workCity = $this->resolveWorkCityFromRequisition($entry->requisition?->city?->name);

        return [
            'work_city_code' => $workCity['code'],
            'work_city_name' => $workCity['name'],
        ];
    }

    /**
     * Asegura ciudad de trabajo desde la requisicion (city_id → catalogo Ciudad) cuando el perfil no la tiene.
     * Persistible: si el perfil existe, actualiza en BD.
     */
    public function ensureWorkCityFromRequisition(PersonalRequisitionFichaEntry $entry, ?EmployeeFichaProfile $profile = null): ?EmployeeFichaProfile
    {
        $entry->loadMissing(['requisition.city', 'profile']);
        $profile ??= $entry->profile;

        if ($profile === null) {
            return null;
        }

        if (filled($profile->work_city_code) || filled($profile->work_city_name)) {
            return $profile;
        }

        $workCity = $this->workCityAttributesFromEntry($entry);

        if ($workCity['work_city_code'] === null && $workCity['work_city_name'] === null) {
            return $profile;
        }

        $profile->fill($workCity);

        if ($profile->exists) {
            $profile->save();
        }

        return $profile;
    }

    /**
     * Datos de solo lectura para el bloque "Referencia de requisicion" (no se persisten como exportables).
     *
     * @return array<string, mixed>|null
     */
    public function requisitionReferenceForEntry(PersonalRequisitionFichaEntry $entry): ?array
    {
        $entry->loadMissing(['requisition.position', 'requisition.city', 'requisition.contractType', 'requisition.client']);

        $requisition = $entry->requisition;

        if ($requisition === null) {
            return null;
        }

        return [
            'code' => $requisition->code,
            'client_name' => $requisition->client?->name,
            'position_name' => $requisition->position?->name,
            'contract_type_name' => $requisition->contractType?->name,
            'base_salary' => $requisition->base_salary,
            'hiring_date' => $requisition->hiring_date?->format('Y-m-d') ?? $requisition->request_date?->format('Y-m-d'),
            'cost_center_hint' => $requisition->cost_center,
            'city_name' => $requisition->city?->name,
        ];
    }

    /**
     * @param  array<string, mixed>  $prefillAttributes
     */
    private function withMissingWorkCity(EmployeeFichaProfile $profile, array $prefillAttributes): EmployeeFichaProfile
    {
        if (filled($profile->work_city_code) || filled($profile->work_city_name)) {
            return $profile;
        }

        if (! filled($prefillAttributes['work_city_code'] ?? null) && ! filled($prefillAttributes['work_city_name'] ?? null)) {
            return $profile;
        }

        $profile->work_city_code = $prefillAttributes['work_city_code'] ?? null;
        $profile->work_city_name = $prefillAttributes['work_city_name'] ?? null;

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $prefillAttributes
     */
    private function mergeRehireProfile(EmployeeFichaProfile $existing, array $prefillAttributes): EmployeeFichaProfile
    {
        $merged = $existing->replicate();
        $merged->exists = true;
        $merged->id = $existing->id;

        $merged->fill(collect($prefillAttributes)->only([
            'salary',
            'hire_date',
            'position_code',
            'position_name',
            'work_city_code',
            'work_city_name',
            'employment_status',
        ])->all());

        $merged->employment_status = EmployeeFichaProfile::STATUS_ACTIVO;
        $merged->termination_date = null;

        return $merged;
    }

    /**
     * Atributos editables sugeridos desde requisicion. No incluye campos exportables a plantilla
     * masivos/nomina que deban salir exclusivamente del catalogo (centro costo, residencia, centro trabajo).
     * Si incluye ciudad de trabajo (no va a plantilla nomina; viene de la ciudad de la requisicion).
     *
     * @return array<string, mixed>
     */
    private function attributesForEntry(PersonalRequisitionFichaEntry $entry): array
    {
        $requisition = $entry->requisition;
        $firstSurname = $entry->first_surname ?: $requisition?->hired_first_surname;
        $secondSurname = $entry->second_surname ?: $requisition?->hired_second_surname;
        $firstName = $entry->first_name ?: $requisition?->hired_first_name;
        $secondName = $entry->second_name ?: $requisition?->hired_second_name;

        if (! $firstSurname && ! $firstName && $entry->hired_full_name) {
            $parsed = EmployeeFichaNameParser::parse($entry->hired_full_name);
            $firstSurname = $parsed['first_surname'];
            $secondSurname = $parsed['second_surname'];
            $firstName = $parsed['first_name'];
            $secondName = $parsed['second_name'];
        }

        $nameParts = array_filter([$firstSurname, $secondSurname, $firstName, $secondName], fn ($v) => $v !== null && $v !== '');
        $fullName = $nameParts !== [] ? implode(' ', $nameParts) : $entry->hired_full_name;
        $payrollPositionCode = null;

        if ($requisition?->position_id) {
            $payrollPositionCode = RequisitionPositionPayrollMap::query()
                ->where('requisition_position_id', $requisition->position_id)
                ->value('payroll_position_code');
        }

        $workCity = $this->workCityAttributesFromEntry($entry);

        return [
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $fullName,
            'first_surname' => $firstSurname,
            'second_surname' => $secondSurname,
            'first_name' => $firstName,
            'second_name' => $secondName,
            'document_type' => 'C',
            'sex' => $this->mapSex($requisition?->sex),
            'salary' => $requisition?->base_salary,
            'hire_date' => $requisition?->hiring_date ?? $requisition?->request_date,
            'contract_end_date' => null,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_code' => $payrollPositionCode,
            'position_name' => $requisition?->position?->name,
            'work_city_code' => $workCity['work_city_code'],
            'work_city_name' => $workCity['work_city_name'],
        ];
    }

    /**
     * @return array{code: ?string, name: ?string}
     */
    private function resolveWorkCityFromRequisition(?string $cityName): array
    {
        $name = trim((string) $cityName);

        if ($name === '') {
            return ['code' => null, 'name' => null];
        }

        $catalog = PayrollCatalogItem::query()
            ->ofType('city')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($catalog === null) {
            $catalog = PayrollCatalogItem::upsertPair('city', null, $name);
        }

        return [
            'code' => $catalog?->code,
            'name' => $catalog?->name ?: $name,
        ];
    }

    private function mapSex(?string $sex): ?string
    {
        return match ($sex) {
            'masculino' => 'M',
            'femenino' => 'F',
            default => null,
        };
    }
}
