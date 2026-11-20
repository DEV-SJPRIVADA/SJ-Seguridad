<?php

namespace App\Http\Requests\Calidad\MtGi11;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Services\Access\MtGi11AccessService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de solicitud MT-GI-11.
 *
 * No valida unicidad de `consecutivo` a propósito: el mismo consecutivo puede
 * repetirse en versiones distintas del documento.
 */
class StoreMtGi11SolicitudRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(MtGi11AccessService::class)->canEdit($user);
    }

    /**
     * La fecha de solicitud no se captura en el formulario: el sistema usa la
     * fecha del día en que se registra. Se inyecta aquí para que la validación
     * de `fecha_estandarizacion` siga comparando contra una fecha conocida y
     * para que un valor enviado por el cliente nunca sobrescriba la regla.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['fecha_solicitud' => now()->toDateString()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxFiles = (int) config('mt_gi_11.adjuntos.max_files_per_upload', 10);
        $maxKilobytes = (int) config('mt_gi_11.adjuntos.max_kilobytes', 10240);
        $mimes = implode(',', config('mt_gi_11.adjuntos.mimes', ['pdf', 'jpg', 'jpeg', 'png']));

        return [
            'fecha_solicitud' => ['required', 'date', 'before_or_equal:today'],
            'proceso_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_procesos,id'],
            'consecutivo' => ['required', 'string', 'max:60'],
            'tipo_documento_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_tipos_documento,id'],
            'nombre_documento' => ['required', 'string', 'max:255'],
            'tipo_solicitud_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_tipos_solicitud,id'],
            'codigo_actual' => ['nullable', 'string', 'max:60'],
            'version_actual' => ['nullable', 'string', 'max:30'],
            'justificacion' => ['required', 'string', 'max:2000'],
            'responsable_proceso' => ['required', 'string', 'max:255'],
            'cargo_responsable_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_cargos_responsable,id'],
            'codigo_estandarizacion' => ['nullable', 'string', 'max:60'],
            'fecha_estandarizacion' => ['nullable', 'date', 'after_or_equal:fecha_solicitud'],
            'version_actualizada' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'estado_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_estados,id'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'max:'.$maxKilobytes, 'mimes:'.$mimes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.*.mimes' => 'Tipo de archivo no permitido. Use PDF, JPG o PNG.',
            'attachments.*.max' => 'Cada adjunto no puede superar 10 MB.',
            'attachments.max' => 'Puede adjuntar máximo 10 archivos por envío.',
            'fecha_solicitud.before_or_equal' => 'La fecha de solicitud no puede ser futura.',
            'fecha_estandarizacion.after_or_equal' => 'La fecha de estandarización no puede ser anterior a la fecha de solicitud.',
            'justificacion.max' => 'La justificación no puede superar 2000 caracteres.',
        ];
    }
}
