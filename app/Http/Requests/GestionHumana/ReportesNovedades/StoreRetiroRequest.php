<?php

namespace App\Http\Requests\GestionHumana\ReportesNovedades;

use App\Services\Access\ReportesNovedadesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRetiroRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ReportesNovedadesAccessService::class)->canEdit($user, 'retiros');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $motivos = config('reportes_novedades.catalogos.retiros_motivo', []);

        return [
            'document_number' => ['required', 'string', 'max:50'],
            'employee_name' => ['required', 'string', 'max:255'],
            'fecha_ingreso' => ['nullable', 'date'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'destino' => ['nullable', 'string', 'max:255'],
            'novedad' => ['required', 'string', 'max:80'],
            'fecha_retiro' => ['nullable', 'date'],
            'motivo_retiro' => ['nullable', 'string', 'max:80', Rule::in($motivos)],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo_retiro.in' => 'El motivo de retiro no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaultNovedad = (string) config('reportes_novedades.retiros_novedad_default', 'RETIRO');

        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'employee_name' => trim((string) $this->input('employee_name')),
            'fecha_ingreso' => $this->nullableDate('fecha_ingreso'),
            'tipo' => $this->nullableTrim('tipo'),
            'cargo' => $this->nullableTrim('cargo'),
            'destino' => $this->nullableTrim('destino'),
            'novedad' => trim((string) ($this->input('novedad') ?: $defaultNovedad)),
            'fecha_retiro' => $this->nullableDate('fecha_retiro'),
            'motivo_retiro' => $this->nullableTrim('motivo_retiro'),
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

    private function nullableDate(string $key): ?string
    {
        if (! $this->exists($key) || $this->input($key) === null) {
            return null;
        }

        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
