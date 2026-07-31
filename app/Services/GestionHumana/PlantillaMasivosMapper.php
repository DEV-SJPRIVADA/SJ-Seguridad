<?php

namespace App\Services\GestionHumana;

use App\Models\PersonalRequisitionFichaEntry;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PlantillaMasivosMapper
{
    /**
     * @return list<mixed>
     */
    public function mapRow(PersonalRequisitionFichaEntry $entry): array
    {
        $entry->loadMissing(['profile', 'requisition.position', 'requisition.city', 'requisition.contractType', 'requisition.client']);
        $profile = $entry->profile;
        $requisition = $entry->requisition;
        $extra = $profile?->payroll_extra ?? [];

        $fullName = $profile?->full_name ?: $entry->hired_full_name;
        $parsed = EmployeeFichaNameParser::parse($fullName);

        return [
            $profile?->document_number ?: $entry->hired_document,
            $profile?->document_type ?: 'C',
            $fullName,
            $profile?->first_surname ?: $parsed['first_surname'],
            $profile?->second_surname ?: $parsed['second_surname'],
            $profile?->first_name ?: $parsed['first_name'],
            $profile?->second_name ?: $parsed['second_name'],
            $profile?->address,
            $profile?->phone,
            $profile?->phone_secondary ?: data_get($extra, 'phone_secondary'),
            $profile?->email,
            $profile?->residence_city_code,
            $profile?->residence_city_name ?: $requisition?->city?->name,
            $this->excelDate($profile?->birth_date),
            $this->excelDate($profile?->hire_date ?? $requisition?->hiring_date ?? $requisition?->request_date),
            data_get($extra, 'vacation_base_date'),
            $profile?->linkage_type,
            $profile?->position_code,
            $profile?->position_name ?: $requisition?->position?->name,
            $profile?->payment_method_code,
            $profile?->bank_code,
            $profile?->bank_name,
            $profile?->account_number,
            $profile?->account_type,
            data_get($extra, 'work_center_code'),
            data_get($extra, 'work_center_nit'),
            $profile?->work_center_name ?: $requisition?->client?->name,
            $profile?->salary ?? $requisition?->base_salary,
            $profile?->eps_code,
            $profile?->eps_name,
            $this->excelDate(data_get($extra, 'eps_start_date')),
            $profile?->afp_code,
            $profile?->afp_name,
            $this->excelDate(data_get($extra, 'afp_start_date')),
            data_get($extra, 'arp_code'),
            $profile?->arp_name,
            $profile?->risk_level,
            data_get($extra, 'ccf_code'),
            $profile?->compensation_fund_name,
            data_get($extra, 'military_book'),
            $profile?->sex ?: $this->mapSex($requisition?->sex),
            $profile?->salary_type_code,
            $profile?->salary_type_name,
            $profile?->contract_type_code,
            $profile?->contract_type_name ?: $requisition?->contractType?->name,
            $this->excelDate($profile?->contract_end_date),
            data_get($extra, 'workday', 1),
            data_get($extra, 'withholding_type', 1),
            data_get($extra, 'expense_type', 4),
            data_get($extra, 'severance_admin_code'),
            data_get($extra, 'severance_admin_name'),
            data_get($extra, 'branch_code'),
            data_get($extra, 'branch_name'),
            $profile?->cost_center_code ?: $requisition?->cost_center,
            $profile?->cost_center_name,
            data_get($extra, 'destination_code'),
            data_get($extra, 'destination_name'),
            data_get($extra, 'zone_code'),
            data_get($extra, 'zone_name'),
            $profile?->economic_activity_code,
            $profile?->economic_activity_name,
            data_get($extra, 'exclude_overtime', 0),
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

    private function excelDate(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return Date::PHPToExcel($value->copy()->startOfDay());
        }

        try {
            return Date::PHPToExcel(Carbon::parse($value)->startOfDay());
        } catch (\Throwable) {
            return $value;
        }
    }
}
