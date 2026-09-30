<?php

namespace App\Http\Requests\GestionHumana\ReportesNovedades;

use App\Services\Access\ReportesNovedadesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ReportesNovedadesAccessService::class)->canEdit($user, 'permisos');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $catalog = config('reportes_novedades.catalogos.permisos_novedad', []);

        return [
            'document_number' => ['required', 'string', 'max:50'],
            'employee_name' => ['required', 'string', 'max:255'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'novedad' => ['required', 'string', 'max:80', Rule::in($catalog)],
            'dias_novedad' => ['required', 'integer', 'min:0', 'max:65535'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'marca_gh' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'novedad.in' => 'La novedad no es válida para Permisos.',
            'fecha_fin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'employee_name' => trim((string) $this->input('employee_name')),
            'tipo' => $this->nullableTrim('tipo'),
            'cargo' => $this->nullableTrim('cargo'),
            'novedad' => trim((string) $this->input('novedad')),
            'marca_gh' => $this->boolean('marca_gh'),
        ]);
    }

    private function nullableTrim(string $key): ?string
    {
        if (! $this->exists($key) || $this->input($key) === null) {
            return null;
        }

        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
