<?php

namespace App\Http\Requests\GestionHumana\Cursos;

use App\Models\CursoTipo;
use App\Services\Access\CursosAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCursoTipoRequest extends FormRequest
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
        /** @var CursoTipo $cursoTipo */
        $cursoTipo = $this->route('cursoTipo');

        return [
            'tipo_curso' => [
                'required',
                'string',
                'max:150',
                Rule::unique('curso_tipos', 'tipo_curso')->ignore($cursoTipo->id),
            ],
            'cargo_curso' => ['nullable', 'string', 'max:150'],
            'formato_para_cursos' => ['nullable', 'string', 'max:150'],
            'cursos' => ['nullable', 'string', 'max:255'],
            'cargo_acredit' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_curso.required' => 'El tipo de curso es obligatorio.',
            'tipo_curso.unique' => 'Ya existe un tipo de curso con ese nombre.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipo_curso' => trim((string) $this->input('tipo_curso')),
            'cargo_curso' => $this->nullableTrim('cargo_curso'),
            'formato_para_cursos' => $this->nullableTrim('formato_para_cursos'),
            'cursos' => $this->nullableTrim('cursos'),
            'cargo_acredit' => $this->nullableTrim('cargo_acredit'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    private function nullableTrim(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
