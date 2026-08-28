<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
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
        $entry->loadMissing(['profile']);
        $profile = $entry->profile;
        $extra = is_array($profile?->payroll_extra) ? $profile->payroll_extra : [];

        $firstSurname = $profile?->first_surname ?: $entry->first_surname;
        $secondSurname = $profile?->second_surname ?: $entry->second_surname;
        $firstName = $profile?->first_name ?: $entry->first_name;
        $secondName = $profile?->second_name ?: $entry->second_name;
        $fullName = $profile?->full_name ?: $entry->hired_full_name;

        if (! $firstSurname && ! $firstName && $fullName) {
            $parsed = EmployeeFichaNameParser::parse($fullName);
            $firstSurname = $parsed['first_surname'];
            $secondSurname = $parsed['second_surname'];
            $firstName = $parsed['first_name'];
            $secondName = $parsed['second_name'];
        }

        $residenceCityCode = $profile?->residence_city_code;
        $residenceCityName = $profile?->residence_city_name ?: $this->resolveCatalogName('city', $residenceCityCode);

        $positionCode = $profile?->position_code;
        $positionName = $profile?->position_name ?: $this->resolveCatalogName('position', $positionCode);

        $bankCode = $profile?->bank_code;
        $bankName = $profile?->bank_name ?: $this->resolveCatalogName('bank', $bankCode);

        $workCenterCode = data_get($extra, 'work_center_code');
        $workCenterName = $profile?->work_center_name ?: $this->resolveCatalogName('work_center', $workCenterCode);

        $epsCode = $profile?->eps_code;
        $epsName = $profile?->eps_name ?: $this->resolveCatalogName('eps', $epsCode);

        $afpCode = $profile?->afp_code;
        $afpName = $profile?->afp_name ?: $this->resolveCatalogName('afp', $afpCode);

        $arpCode = data_get($extra, 'arp_code');
        $arpName = $profile?->arp_name ?: $this->resolveCatalogName('arp', $arpCode);

        $ccfCode = data_get($extra, 'ccf_code');
        $ccfName = $profile?->compensation_fund_name ?: $this->resolveCatalogName('ccf', $ccfCode);

        $salaryTypeCode = $profile?->salary_type_code;
        $salaryTypeName = $profile?->salary_type_name ?: $this->resolveCatalogName('salary_type', $salaryTypeCode);

        $contractTypeCode = $profile?->contract_type_code;
        $contractTypeName = $profile?->contract_type_name ?: $this->resolveCatalogName('contract_type', $contractTypeCode);

        $severanceAdminCode = data_get($extra, 'severance_admin_code');
        $severanceAdminName = data_get($extra, 'severance_admin_name') ?: $this->resolveCatalogName('severance_admin', $severanceAdminCode);

        $branchCode = data_get($extra, 'branch_code');
        $branchName = data_get($extra, 'branch_name') ?: $this->resolveCatalogName('branch', $branchCode);

        $costCenterCode = $profile?->cost_center_code;
        $costCenterName = $profile?->cost_center_name ?: $this->resolveCatalogName('cost_center', $costCenterCode);

        $destinationCode = data_get($extra, 'destination_code');
        $destinationName = data_get($extra, 'destination_name') ?: $this->resolveCatalogName('destination', $destinationCode);

        $zoneCode = data_get($extra, 'zone_code');
        $zoneName = data_get($extra, 'zone_name') ?: $this->resolveCatalogName('zone', $zoneCode);

        $economicActivityCode = $profile?->economic_activity_code;
        $economicActivityName = $profile?->economic_activity_name ?: $this->resolveCatalogName('economic_activity', $economicActivityCode);

        return [
            $profile?->document_number ?: $entry->hired_document,
            $this->documentTypeCode($profile?->document_type),
            $fullName,
            $firstSurname,
            $secondSurname,
            $firstName,
            $secondName,
            $profile?->address,
            $profile?->phone,
            $profile?->phone_secondary ?: data_get($extra, 'phone_secondary'),
            $profile?->email,
            $residenceCityCode,
            $residenceCityName,
            $this->excelDate($profile?->birth_date),
            $this->excelDate($profile?->hire_date),
            data_get($extra, 'vacation_base_date'),
            $profile?->linkage_type,
            $positionCode,
            $positionName,
            $profile?->payment_method_code,
            $bankCode,
            $bankName,
            $profile?->account_number,
            $profile?->account_type,
            $workCenterCode,
            null,
            $workCenterName,
            $profile?->salary,
            $epsCode,
            $epsName,
            $this->excelDate(data_get($extra, 'eps_start_date')),
            $afpCode,
            $afpName,
            $this->excelDate(data_get($extra, 'afp_start_date')),
            $arpCode,
            $arpName,
            $profile?->risk_level,
            $ccfCode,
            $ccfName,
            data_get($extra, 'military_book'),
            $profile?->sex,
            $salaryTypeCode,
            $salaryTypeName,
            $contractTypeCode,
            $contractTypeName,
            $this->excelDate($profile?->contract_end_date),
            data_get($extra, 'workday'),
            data_get($extra, 'withholding_type'),
            data_get($extra, 'expense_type'),
            $severanceAdminCode,
            $severanceAdminName,
            $branchCode,
            $branchName,
            $costCenterCode,
            $costCenterName,
            $destinationCode,
            $destinationName,
            $zoneCode,
            $zoneName,
            $economicActivityCode,
            $economicActivityName,
            data_get($extra, 'exclude_overtime'),
        ];
    }

    private function resolveCatalogName(string $type, ?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return PayrollCatalogItem::query()
            ->ofType($type)
            ->where('code', $code)
            ->value('name');
    }

    private function documentTypeCode(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, ' — ')) {
            return trim(explode(' — ', $value, 2)[0]);
        }

        return $value;
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
