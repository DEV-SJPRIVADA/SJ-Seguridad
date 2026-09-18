<?php

namespace App\Http\Requests\GestionHumana\Cursos;

use App\Models\CursoEscuela;
use App\Services\Access\CursosAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCursoEscuelaRequest extends FormRequest
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
        /** @var CursoEscuela $cursoEscuela */
        $cursoEscuela = $this->route('cursoEscuela');

        return [
            'codigo' => [
                'required',
                'string',
                'max:30',
                Rule::unique('curso_escuelas', 'codigo')->ignore($cursoEscuela->id),
            ],
            'nit' => ['required', 'string', 'max:30'],
            'nombre' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.required' => 'El codigo es obligatorio.',
            'codigo.unique' => 'Ya existe una escuela con ese codigo.',
            'nit.required' => 'El NIT es obligatorio.',
            'nombre.required' => 'El nombre es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo' => trim((string) $this->input('codigo')),
            'nit' => trim((string) $this->input('nit')),
            'nombre' => trim((string) $this->input('nombre')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
