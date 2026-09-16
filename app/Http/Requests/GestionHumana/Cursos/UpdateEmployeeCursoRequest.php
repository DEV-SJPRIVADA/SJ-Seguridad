<?php

namespace App\Http\Requests\GestionHumana\Cursos;

use App\Models\EmployeeCurso;
use App\Services\Access\CursosAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeCursoRequest extends FormRequest
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
        /** @var EmployeeCurso $curso */
        $curso = $this->route('employeeCurso');

        return [
            'document_number' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'curso_tipo_id' => ['required', 'integer', Rule::exists('curso_tipos', 'id')],
            'fecha_expedicion' => ['required', 'date'],
            'numero_curso' => [
                'required',
                'string',
                'max:100',
                Rule::unique('employee_cursos', 'numero_curso')
                    ->where(fn ($query) => $query->where('document_number', $this->input('document_number')))
                    ->ignore($curso->id),
            ],
            'estado' => ['nullable', 'string', Rule::in(EmployeeCurso::ESTADOS)],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'La cédula es obligatoria.',
            'full_name.required' => 'El nombre completo es obligatorio.',
            'curso_tipo_id.required' => 'El tipo de curso es obligatorio.',
            'curso_tipo_id.exists' => 'El tipo de curso no existe en el catálogo.',
            'fecha_expedicion.required' => 'La fecha de expedición es obligatoria.',
            'numero_curso.required' => 'El número de curso es obligatorio.',
            'numero_curso.unique' => 'Ya existe un curso con esa cédula y número.',
            'estado.in' => 'El estado no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $estado = trim((string) $this->input('estado'));

        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'full_name' => trim((string) $this->input('full_name')),
            'numero_curso' => trim((string) $this->input('numero_curso')),
            'estado' => $estado === '' ? null : $estado,
            'observaciones' => $this->nullableTrim('observaciones'),
        ]);
    }

    private function nullableTrim(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
