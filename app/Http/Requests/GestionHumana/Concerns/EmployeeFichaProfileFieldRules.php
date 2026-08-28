<?php

namespace App\Http\Requests\GestionHumana\Concerns;

use App\Rules\GestionHumana\PayrollCatalogCode;
use App\Support\ColombianCurrencyParser;
use Illuminate\Validation\Rule;

trait EmployeeFichaProfileFieldRules
{
    protected function prepareForValidation(): void
    {
        if ($this->has('salary')) {
            $this->merge([
                'salary' => ColombianCurrencyParser::parse($this->input('salary')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function employeeFichaProfileFieldRules(bool $requireCore = true): array
    {
        $required = $requireCore ? 'required' : 'nullable';

        return array_merge(
            $this->employeeFichaProfileScalarRules($required),
            $this->employeeFichaPayrollExtraRules($required),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeFichaProfileScalarRules(string $required): array
    {
        return [
            'first_surname' => [$required, 'string', 'max:100'],
            'second_surname' => ['nullable', 'string', 'max:100'],
            'first_name' => [$required, 'string', 'max:100'],
            'second_name' => ['nullable', 'string', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:20', new PayrollCatalogCode('document_type')],
            'birth_date' => ['nullable', 'date'],
            'expedition_city_code' => ['nullable', 'string', 'max:20', new PayrollCatalogCode('city')],
            'expedition_city_name' => ['nullable', 'string', 'max:100'],
            'expedition_date' => ['nullable', 'date'],
            'residence_city_code' => ['nullable', 'string', 'max:20', new PayrollCatalogCode('city')],
            'residence_city_name' => ['nullable', 'string', 'max:100'],
            'work_city_code' => ['nullable', 'string', 'max:20', new PayrollCatalogCode('city')],
            'work_city_name' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_secondary' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'sex' => [$required, 'string', Rule::in(['M', 'F'])],
            'salary' => [$required, 'numeric', 'min:0', 'max:999999999999.99'],
            'hire_date' => [$required, 'date'],
            'contract_end_date' => ['nullable', 'date'],
            'work_center_name' => ['nullable', 'string', 'max:150'],
            'cost_center_code' => [$required, 'string', 'max:50', new PayrollCatalogCode('cost_center')],
            'cost_center_name' => ['nullable', 'string', 'max:150'],
            'position_code' => [$required, 'string', 'max:50', new PayrollCatalogCode('position')],
            'position_name' => ['nullable', 'string', 'max:150'],
            'salary_type_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('salary_type')],
            'contract_type_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('contract_type')],
            'contract_type_name' => ['nullable', 'string', 'max:100'],
            'eps_code' => [$required, 'string', 'max:50', new PayrollCatalogCode('eps')],
            'eps_name' => ['nullable', 'string', 'max:150'],
            'afp_code' => [$required, 'string', 'max:50', new PayrollCatalogCode('afp')],
            'afp_name' => ['nullable', 'string', 'max:150'],
            'arp_name' => ['nullable', 'string', 'max:150'],
            'risk_level' => ['nullable', 'string', 'max:20', new PayrollCatalogCode('risk_level')],
            'compensation_fund_name' => ['nullable', 'string', 'max:150'],
            'bank_code' => [$required, 'string', 'max:50', new PayrollCatalogCode('bank')],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'account_type' => [$required, 'string', 'max:10', new PayrollCatalogCode('account_type')],
            'account_number' => [$required, 'string', 'max:50'],
            'payment_method_code' => [$required, 'string', 'max:50', new PayrollCatalogCode('payment_method')],
            'economic_activity_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('economic_activity')],
            'economic_activity_name' => ['nullable', 'string', 'max:150'],
            'linkage_type' => ['nullable', 'string', 'max:100', new PayrollCatalogCode('linkage_type')],
            'salary_type_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeFichaPayrollExtraRules(string $required): array
    {
        return [
            'payroll_extra' => ['nullable', 'array'],
            'payroll_extra.ccf_code' => [$required, 'string', 'max:50', new PayrollCatalogCode('ccf')],
            'payroll_extra.work_center_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('work_center')],
            'payroll_extra.branch_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('branch')],
            'payroll_extra.destination_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('destination')],
            'payroll_extra.zone_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('zone')],
            'payroll_extra.severance_admin_code' => ['nullable', 'string', 'max:50', new PayrollCatalogCode('severance_admin')],
            'payroll_extra.workday' => ['nullable', 'string', 'max:10', new PayrollCatalogCode('workday')],
            'payroll_extra.withholding_type' => ['nullable', 'string', 'max:10', new PayrollCatalogCode('withholding_type')],
            'payroll_extra.expense_type' => ['nullable', 'string', 'max:10', new PayrollCatalogCode('expense_type')],
            'payroll_extra.eps_start_date' => ['nullable', 'date'],
            'payroll_extra.afp_start_date' => ['nullable', 'date'],
            'payroll_extra.vacation_base_date' => ['nullable', 'date'],
            'payroll_extra.arp_code' => ['nullable', 'string', 'max:50'],
            'payroll_extra.military_book' => ['nullable', 'string', 'max:50'],
            'payroll_extra.exclude_overtime' => ['nullable', 'string', 'max:5'],
        ];
    }
}
