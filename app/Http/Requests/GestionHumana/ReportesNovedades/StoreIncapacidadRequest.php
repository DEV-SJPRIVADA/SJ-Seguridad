<?php

namespace App\Http\Requests\GestionHumana\ReportesNovedades;

use App\Services\Access\ReportesNovedadesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncapacidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ReportesNovedadesAccessService::class)->canEdit($user, 'incapacidades');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $catalog = config('reportes_novedades.catalogos.incapacidades_tipo', []);

        return [
            'document_number' => ['required', 'string', 'max:50'],
            'employee_name' => ['required', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'destino' => ['nullable', 'string', 'max:255'],
            'tipo_incapacidad' => ['required', 'string', 'max:40', Rule::in($catalog)],
            'dias' => ['required', 'integer', 'min:0', 'max:65535'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'fecha_recepcion' => ['nullable', 'date'],
            'fecha_devolucion' => ['nullable', 'date'],
            'observacion_devolucion' => ['nullable', 'string'],
            'fecha_registro_control_roll' => ['nullable', 'date'],
            'fecha_envio_final' => ['nullable', 'date'],
            'novedad_control_roll' => ['nullable', 'string', 'max:120'],
            'extemporanea' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_incapacidad.in' => 'El tipo de incapacidad no es válido.',
            'fecha_fin.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'employee_name' => trim((string) $this->input('employee_name')),
            'cargo' => $this->nullableTrim('cargo'),
            'destino' => $this->nullableTrim('destino'),
            'tipo_incapacidad' => trim((string) $this->input('tipo_incapacidad')),
            'observacion_devolucion' => $this->nullableTrim('observacion_devolucion'),
            'novedad_control_roll' => $this->nullableTrim('novedad_control_roll'),
            'observaciones' => $this->nullableTrim('observaciones'),
            'extemporanea' => $this->boolean('extemporanea'),
            'fecha_recepcion' => $this->nullableDate('fecha_recepcion'),
            'fecha_devolucion' => $this->nullableDate('fecha_devolucion'),
            'fecha_registro_control_roll' => $this->nullableDate('fecha_registro_control_roll'),
            'fecha_envio_final' => $this->nullableDate('fecha_envio_final'),
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
