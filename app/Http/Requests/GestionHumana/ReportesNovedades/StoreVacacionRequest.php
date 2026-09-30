<?php

namespace App\Http\Requests\GestionHumana\ReportesNovedades;

use App\Services\Access\ReportesNovedadesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVacacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ReportesNovedadesAccessService::class)->canEdit($user, 'vacaciones');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $catalog = config('reportes_novedades.catalogos.vacaciones_novedad', []);

        return [
            'document_number' => ['required', 'string', 'max:50'],
            'employee_name' => ['required', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'destino' => ['nullable', 'string', 'max:255'],
            'novedad' => ['required', 'string', 'max:80', Rule::in($catalog)],
            'dias_novedad' => ['required', 'integer', 'min:0', 'max:65535'],
            'fecha_inicio' => ['required', 'date'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'novedad.in' => 'La novedad no es válida para Vacaciones.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'employee_name' => trim((string) $this->input('employee_name')),
            'cargo' => $this->nullableTrim('cargo'),
            'destino' => $this->nullableTrim('destino'),
            'novedad' => trim((string) $this->input('novedad')),
            'observaciones' => $this->nullableTrim('observaciones'),
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
