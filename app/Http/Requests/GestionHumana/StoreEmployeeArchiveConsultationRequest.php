<?php

namespace App\Http\Requests\GestionHumana;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeArchiveConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('archivo.view') ?? false) || ($user?->can('archivo.manage') ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $typeKeys = array_keys(config('employee_ficha.archive_consultation_types', []));

        return [
            'documents' => ['required', 'string', 'max:5000'],
            'delivered_to' => ['nullable', 'string', 'max:150'],
            'consultation_types' => ['required', 'array', 'min:1'],
            'consultation_types.*' => ['required', 'string', Rule::in($typeKeys)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.required' => 'Ingrese al menos una cedula para consultar.',
            'consultation_types.required' => 'Debe seleccionar un motivo de consulta.',
            'consultation_types.min' => 'Debe seleccionar un motivo de consulta.',
            'consultation_types.*.in' => 'Uno de los motivos seleccionados no es valido.',
        ];
    }
}
