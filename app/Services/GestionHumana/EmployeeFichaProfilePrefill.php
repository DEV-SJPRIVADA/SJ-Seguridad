<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
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

            return $entry->profile;
        }

        return new EmployeeFichaProfile($prefillAttributes);
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
            'employment_status',
        ])->all());

        $merged->employment_status = EmployeeFichaProfile::STATUS_ACTIVO;
        $merged->termination_date = null;

        return $merged;
    }

    /**
     * Atributos editables sugeridos desde requisicion. No incluye campos exportables a plantilla
     * masivos que deban salir exclusivamente del catalogo (centro costo, ciudad, centro trabajo).
     *
     * @return array<string, mixed>
     */
    private function attributesForEntry(PersonalRequisitionFichaEntry $entry): array
    {
        $requisition = $entry->requisition;
        $parsed = EmployeeFichaNameParser::parse($entry->hired_full_name);
        $payrollPositionCode = null;

        if ($requisition?->position_id) {
            $payrollPositionCode = RequisitionPositionPayrollMap::query()
                ->where('requisition_position_id', $requisition->position_id)
                ->value('payroll_position_code');
        }

        return [
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $entry->hired_document,
            'full_name' => $parsed['full_name'] ?: $entry->hired_full_name,
            'first_surname' => $parsed['first_surname'],
            'second_surname' => $parsed['second_surname'],
            'first_name' => $parsed['first_name'],
            'second_name' => $parsed['second_name'],
            'document_type' => 'C',
            'sex' => $this->mapSex($requisition?->sex),
            'salary' => $requisition?->base_salary,
            'hire_date' => $requisition?->hiring_date ?? $requisition?->request_date,
            'contract_end_date' => null,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_code' => $payrollPositionCode,
            'position_name' => $requisition?->position?->name,
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
