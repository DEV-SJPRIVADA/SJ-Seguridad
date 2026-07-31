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

        $requisition = $entry->requisition;
        $parsed = EmployeeFichaNameParser::parse($entry->hired_full_name);
        $payrollPositionCode = null;

        if ($requisition?->position_id) {
            $payrollPositionCode = RequisitionPositionPayrollMap::query()
                ->where('requisition_position_id', $requisition->position_id)
                ->value('payroll_position_code');
        }

        $profile = EmployeeFichaProfile::query()->create([
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
            'cost_center_code' => $requisition?->cost_center,
            'position_code' => $payrollPositionCode,
            'position_name' => $requisition?->position?->name,
            'contract_type_name' => $requisition?->contractType?->name,
            'residence_city_name' => $requisition?->city?->name,
            'work_center_name' => $requisition?->client?->name,
        ]);

        return $profile;
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
