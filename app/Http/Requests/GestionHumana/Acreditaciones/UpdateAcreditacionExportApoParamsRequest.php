<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Services\Access\AcreditacionesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAcreditacionExportApoParamsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(AcreditacionesAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nit' => ['required', 'string', 'max:20'],
            'razon_social' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['required', 'string', 'max:10'],
            'tipo_establecimiento' => ['required', 'string', 'max:50'],
            'telefono_r' => ['required', 'string', 'max:30'],
            'direccion_r' => ['required', 'string', 'max:255'],
            'direccion_p' => ['required', 'string', 'max:255'],
            'departamento' => ['required', 'string', 'max:100'],
            'ciudad' => ['required', 'string', 'max:100'],
            'educacion_bm' => ['required', 'string', 'max:50'],
            'educacion_s' => ['required', 'string', 'max:50'],
            'discapacidad' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nit.required' => 'El Nit es obligatorio.',
            'razon_social.required' => 'La razón social es obligatoria.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_establecimiento.required' => 'El tipo de establecimiento es obligatorio.',
            'telefono_r.required' => 'El teléfono es obligatorio.',
            'direccion_r.required' => 'La dirección R es obligatoria.',
            'direccion_p.required' => 'La dirección P es obligatoria.',
            'departamento.required' => 'El departamento es obligatorio.',
            'ciudad.required' => 'La ciudad es obligatoria.',
            'educacion_bm.required' => 'Educación BM es obligatoria.',
            'educacion_s.required' => 'Educación S es obligatoria.',
            'discapacidad.required' => 'Discapacidad es obligatoria.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $fields = [
            'nit',
            'razon_social',
            'tipo_documento',
            'tipo_establecimiento',
            'telefono_r',
            'direccion_r',
            'direccion_p',
            'departamento',
            'ciudad',
            'educacion_bm',
            'educacion_s',
            'discapacidad',
        ];

        $merged = [];
        foreach ($fields as $field) {
            if ($this->has($field)) {
                $merged[$field] = trim((string) $this->input($field));
            }
        }

        $this->merge($merged);
    }
}
