<?php

namespace App\Http\Requests\GestionHumana;

use App\Http\Requests\GestionHumana\Concerns\EmployeeFichaProfileFieldRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualEmployeeFichaRequest extends FormRequest
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
        $fichaEntryId = $this->input('ficha_entry_id');

        return array_merge([
            'ficha_entry_id' => [
                'nullable',
                'integer',
                Rule::exists('personal_requisition_ficha_entries', 'id')
                    ->where(fn ($query) => $query->whereNull('moved_to_ficha_at')),
            ],
            'hired_document' => [
                'required',
                'string',
                'max:50',
                Rule::unique('personal_requisition_ficha_entries', 'hired_document')->ignore($fichaEntryId),
            ],
            'hired_full_name' => ['nullable', 'string', 'max:255'],
        ], $this->employeeFichaProfileFieldRules(requireCore: true));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'ficha_entry_id' => 'registro pendiente',
            'hired_document' => 'cédula',
            'first_surname' => 'primer apellido',
            'second_surname' => 'segundo apellido',
            'first_name' => 'primer nombre',
            'second_name' => 'segundo nombre',
            'hired_full_name' => 'nombre completo',
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
