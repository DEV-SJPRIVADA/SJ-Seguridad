<?php

namespace App\Http\Requests\GestionHumana;

use App\Http\Requests\GestionHumana\Concerns\EmployeeFichaProfileFieldRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeFichaProfileRequest extends FormRequest
{
    use EmployeeFichaProfileFieldRules;

    public function authorize(): bool
    {
        return $this->user()?->can('ficha_empleados.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->employeeFichaProfileFieldRules(requireCore: true);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sex' => 'género',
            'hire_date' => 'fecha ingreso',
            'position_code' => 'cargo',
            'cost_center_code' => 'centro de costo',
            'eps_code' => 'EPS',
            'afp_code' => 'AFP',
            'bank_code' => 'banco',
            'account_type' => 'tipo de cuenta',
            'account_number' => 'número de cuenta',
            'payment_method_code' => 'forma de pago',
            'payroll_extra.ccf_code' => 'caja de compensación',
        ];
    }
}
