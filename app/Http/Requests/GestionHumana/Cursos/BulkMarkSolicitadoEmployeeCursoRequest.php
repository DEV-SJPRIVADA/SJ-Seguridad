<?php

namespace App\Http\Requests\GestionHumana\Cursos;

use App\Services\Access\CursosAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkMarkSolicitadoEmployeeCursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(CursosAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'distinct', Rule::exists('employee_cursos', 'id')],
            'confirmed' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Debe seleccionar al menos un registro.',
            'ids.min' => 'Debe seleccionar al menos un registro.',
            'ids.max' => 'No puede marcar más de 500 registros a la vez.',
            'ids.*.exists' => 'Uno o más registros seleccionados no existen.',
            'confirmed.accepted' => 'Debe confirmar que comprende que el cambio no se puede revertir desde esta acción.',
        ];
    }
}
