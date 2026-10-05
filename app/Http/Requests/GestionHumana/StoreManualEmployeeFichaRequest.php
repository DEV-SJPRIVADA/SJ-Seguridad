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
        return array_merge($this->employeeFichaProfileFieldAttributes(), [
            'ficha_entry_id' => 'registro pendiente',
            'hired_document' => 'cédula',
            'hired_full_name' => 'nombre completo',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->employeeFichaProfileFieldMessages(), [
            'hired_document.unique' => 'Ya existe un registro con esta cédula. Use otra cédula o gestione el empleado existente.',
            'ficha_entry_id.exists' => 'El registro pendiente no es válido o ya fue movido a ficha.',
        ]);
    }
}
