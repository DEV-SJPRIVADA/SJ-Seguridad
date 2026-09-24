<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\EmployeeFichaProfile;
use App\Services\Access\AcreditacionesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAcreditacionAcreditadoRequest extends FormRequest
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
        $obsMax = (int) config('acreditaciones.limits.observaciones_max', 5000);

        return [
            'document_number' => [
                'required',
                'string',
                'max:'.(int) config('acreditaciones.limits.document_number_max', 50),
                Rule::exists('employee_ficha_profiles', 'document_number'),
            ],
            'cargo' => [
                'required',
                'string',
                'max:'.(int) config('acreditaciones.limits.cargo_max', 255),
            ],
            'cargo_apo' => [
                'required',
                'string',
                'max:'.(int) config('acreditaciones.limits.cargo_apo_max', 255),
                Rule::unique('acreditacion_acreditados', 'cargo_apo')
                    ->where(fn ($query) => $query->where('document_number', $this->input('document_number'))),
            ],
            'vigencia_acr' => ['nullable', 'date'],
            'fecha_solicitud' => ['nullable', 'date'],
            'renovacion' => ['nullable', 'string', Rule::in(AcreditacionAcreditado::RENOVACIONES)],
            'observaciones' => ['nullable', 'string', 'max:'.$obsMax],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'La cédula es obligatoria.',
            'document_number.exists' => 'La cédula no existe en Ficha empleados.',
            'cargo.required' => 'El cargo es obligatorio.',
            'cargo_apo.required' => 'El CARGO APO es obligatorio.',
            'cargo_apo.unique' => 'Ya existe un acreditado con esa cédula y CARGO APO.',
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $vigencia = $this->input('vigencia_acr');
                $solicitud = $this->input('fecha_solicitud');

                if (($vigencia === null || $vigencia === '') && ($solicitud === null || $solicitud === '')) {
                    $validator->errors()->add(
                        'vigencia_acr',
                        'Debe indicar al menos una fecha: VIGEN.ACR o FECHA SOLICITUD.',
                    );
                }

                $cargoApo = trim((string) $this->input('cargo_apo'));
                if ($cargoApo === '') {
                    return;
                }

                $existsActive = AcreditacionCargo::query()
                    ->active()
                    ->forCargoApo($cargoApo)
                    ->exists();

                if (! $existsActive) {
                    $validator->errors()->add(
                        'cargo_apo',
                        'El CARGO APO no existe como valor activo en el catálogo.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
            'cargo' => trim((string) $this->input('cargo')),
            'cargo_apo' => trim((string) $this->input('cargo_apo')),
            'vigencia_acr' => $this->nullableDate('vigencia_acr'),
            'fecha_solicitud' => $this->nullableDate('fecha_solicitud'),
            'renovacion' => $this->nullableTrim('renovacion'),
            'observaciones' => $this->nullableTrim('observaciones'),
        ]);
    }

    /**
     * Nombre desde Ficha (ignora input del cliente).
     */
    public function resolvedFullName(): string
    {
        $cedula = (string) $this->input('document_number');

        return (string) EmployeeFichaProfile::query()
            ->where('document_number', $cedula)
            ->value('full_name');
    }

    private function nullableTrim(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }

    private function nullableDate(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
