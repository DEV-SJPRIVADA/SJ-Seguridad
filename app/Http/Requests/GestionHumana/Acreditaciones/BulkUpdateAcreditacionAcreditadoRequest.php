<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Models\AcreditacionAcreditado;
use App\Services\Access\AcreditacionesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateAcreditacionAcreditadoRequest extends FormRequest
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
        $maxIds = (int) config('acreditaciones.limits.bulk_max_ids', 500);

        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.$maxIds],
            'ids.*' => ['integer', 'distinct', Rule::exists('acreditacion_acreditados', 'id')],
            'observaciones' => ['nullable', 'string', 'max:'.$obsMax],
            'fecha_solicitud' => ['nullable', 'date'],
            'renovacion' => ['nullable', 'string', Rule::in(AcreditacionAcreditado::RENOVACIONES)],
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
            'ids.max' => 'No puede actualizar más de '.config('acreditaciones.limits.bulk_max_ids', 500).' registros a la vez.',
            'ids.*.exists' => 'Uno o más registros seleccionados no existen.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $renovacion = trim((string) $this->input('renovacion', ''));

        $this->merge([
            'renovacion' => $renovacion === '' ? null : $renovacion,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $observaciones = trim((string) $this->input('observaciones', ''));
            $fecha = trim((string) $this->input('fecha_solicitud', ''));
            $renovacion = trim((string) ($this->input('renovacion') ?? ''));

            if ($observaciones === '' && $fecha === '' && $renovacion === '') {
                $validator->errors()->add(
                    'observaciones',
                    'Indique al menos observaciones, fecha de solicitud o renovaciones.',
                );
            }
        });
    }
}
