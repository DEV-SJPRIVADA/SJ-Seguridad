<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Services\Access\AcreditacionesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class ImportAcreditacionReporteDiarioRequest extends FormRequest
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
        $today = Carbon::now(config('app.timezone'))->toDateString();

        return [
            'fecha_reporte' => ['required', 'date', 'before_or_equal:'.$today],
            // extensions (no solo mimes): en Windows el MIME de .xlsx a veces llega como octet-stream.
            'file_proceso' => ['nullable', 'file', 'extensions:xlsx,xls,csv', 'max:20480'],
            'file_acreditado' => ['nullable', 'file', 'extensions:xlsx,xls,csv', 'max:20480'],
            'confirm_replace' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_reporte.required' => 'La fecha de reporte es obligatoria.',
            'fecha_reporte.before_or_equal' => 'La fecha de reporte no puede ser futura.',
            'file_proceso.extensions' => 'El archivo En proceso debe ser Excel (.xlsx, .xls) o CSV.',
            'file_acreditado.extensions' => 'El archivo Acreditado APO debe ser Excel (.xlsx, .xls) o CSV.',
            'file_proceso.max' => 'El archivo En proceso no puede superar 20 MB.',
            'file_acreditado.max' => 'El archivo Acreditado APO no puede superar 20 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasProceso = $this->file('file_proceso') !== null;
            $hasAcreditado = $this->file('file_acreditado') !== null;

            if (! $hasProceso && ! $hasAcreditado) {
                $validator->errors()->add(
                    'file_proceso',
                    'Debe subir al menos un archivo (En proceso o Acreditado APO).',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('confirm_replace')) {
            $this->merge([
                'confirm_replace' => filter_var(
                    $this->input('confirm_replace'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ) ?? false,
            ]);
        }
    }
}
