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
        return array_merge([
            'hired_document' => [
                'required',
                'string',
                'max:50',
                Rule::unique('personal_requisition_ficha_entries', 'hired_document'),
            ],
            'hired_full_name' => ['required', 'string', 'max:255'],
        ], $this->employeeFichaProfileFieldRules());
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hired_document' => 'cédula',
            'hired_full_name' => 'nombre completo',
            'sex' => 'género',
        ];
    }
}
