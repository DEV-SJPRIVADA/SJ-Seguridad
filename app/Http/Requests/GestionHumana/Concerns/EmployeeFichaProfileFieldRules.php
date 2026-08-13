<?php

namespace App\Http\Requests\GestionHumana\Concerns;

use App\Support\ColombianCurrencyParser;

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
    protected function employeeFichaProfileFieldRules(): array
    {
        return [
            'document_type' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'expedition_city_code' => ['nullable', 'string', 'max:20'],
            'expedition_city_name' => ['nullable', 'string', 'max:100'],
            'expedition_date' => ['nullable', 'date'],
            'residence_city_code' => ['nullable', 'string', 'max:20'],
            'residence_city_name' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_secondary' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'sex' => ['nullable', 'string', 'max:5'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'hire_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date'],
            'work_center_name' => ['nullable', 'string', 'max:150'],
            'cost_center_code' => ['nullable', 'string', 'max:50'],
            'cost_center_name' => ['nullable', 'string', 'max:150'],
            'position_code' => ['nullable', 'string', 'max:50'],
            'position_name' => ['nullable', 'string', 'max:150'],
            'salary_type_code' => ['nullable', 'string', 'max:50'],
            'contract_type_code' => ['nullable', 'string', 'max:50'],
            'contract_type_name' => ['nullable', 'string', 'max:100'],
            'eps_code' => ['nullable', 'string', 'max:50'],
            'eps_name' => ['nullable', 'string', 'max:150'],
            'afp_code' => ['nullable', 'string', 'max:50'],
            'afp_name' => ['nullable', 'string', 'max:150'],
            'arp_name' => ['nullable', 'string', 'max:150'],
            'risk_level' => ['nullable', 'string', 'max:20'],
            'compensation_fund_name' => ['nullable', 'string', 'max:150'],
            'bank_code' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'account_type' => ['nullable', 'string', 'max:10'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'payment_method_code' => ['nullable', 'string', 'max:50'],
            'economic_activity_code' => ['nullable', 'string', 'max:50'],
            'economic_activity_name' => ['nullable', 'string', 'max:150'],
            'linkage_type' => ['nullable', 'string', 'max:100'],
            'contributor_type' => ['nullable', 'string', 'max:50'],
        ];
    }
}
