<?php

namespace App\Http\Requests\Calidad\MtGi11;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\Calidad\MtGi11Estado;
use App\Services\Access\MtGi11AccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta de solicitud MT-GI-11.
 *
 * No valida unicidad de `consecutivo` a propósito: el mismo consecutivo puede
 * repetirse en versiones distintas del documento. Al crear, el consecutivo es
 * opcional: si llega vacío el servicio genera uno provisional.
 */
class StoreMtGi11SolicitudRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        // Crear requiere solo consulta de la matriz (`matriz.view`).
        return $user !== null && app(MtGi11AccessService::class)->canCreate($user);
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

        $this->discardEstandarizacionWithoutPermission();
    }

    /**
     * Quien no puede administrar la Estandarización no debe poder enviar sus
     * campos (ni el estado) aunque manipule el POST: se fuerza PENDIENTE.
     *
     * En la edición se sobrescribe para conservar los valores del registro en
     * lugar de vaciarlos (ver UpdateMtGi11SolicitudRequest).
     */
    protected function discardEstandarizacionWithoutPermission(): void
    {
        $user = $this->user();

        if ($user !== null && ! app(MtGi11AccessService::class)->canManageEstandarizacion($user)) {
            $this->merge($this->estandarizacionFallback());
        }
    }

    /**
     * Valores a forzar cuando la sección no está disponible: en el alta es
     * PENDIENTE y campos vacíos.
     *
     * @return array<string, mixed>
     */
    protected function estandarizacionFallback(): array
    {
        return [
            'codigo_estandarizacion' => null,
            'fecha_estandarizacion' => null,
            'version_actualizada' => null,
            'estado_id' => MtGi11Estado::query()->where('code', MtGi11Estado::CODE_PENDIENTE)->value('id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxFiles = (int) config('mt_gi_11.adjuntos.max_files_per_upload', 10);
        $maxKilobytes = (int) config('mt_gi_11.adjuntos.max_kilobytes', 10240);
        $mimes = implode(',', config('mt_gi_11.adjuntos.mimes', ['pdf', 'jpg', 'jpeg', 'png']));

        // Con estado GESTIONADO la solicitud queda cerrada: todos los campos de
        // la solicitud (incluidos los de Estandarización) son obligatorios.
        $gestionado = MtGi11Estado::isGestionadoId($this->input('estado_id'));

        return [
            'fecha_solicitud' => ['required', 'date', 'before_or_equal:today'],
            'proceso_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_procesos,id'],
            'consecutivo' => [Rule::requiredIf($gestionado), 'nullable', 'string', 'max:60'],
            'tipo_documento_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_tipos_documento,id'],
            'nombre_documento' => ['required', 'string', 'max:255'],
            'tipo_solicitud_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_tipos_solicitud,id'],
            'codigo_actual' => [Rule::requiredIf($gestionado), 'nullable', 'string', 'max:60'],
            'version_actual' => [Rule::requiredIf($gestionado), 'nullable', 'string', 'max:30'],
            'justificacion' => ['required', 'string', 'max:2000'],
            'responsable_proceso' => ['required', 'string', 'max:255'],
            'cargo_responsable_id' => ['required', 'integer', 'exists:calidad_mt_gi_11_cargos_responsable,id'],
            'codigo_estandarizacion' => [Rule::requiredIf($gestionado), 'nullable', 'string', 'max:60'],
            'fecha_estandarizacion' => [Rule::requiredIf($gestionado), 'nullable', 'date', 'after_or_equal:fecha_solicitud'],
            'version_actualizada' => [Rule::requiredIf($gestionado), 'nullable', 'integer', 'min:0', 'max:9999'],
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
            // Con estado GESTIONADO la solicitud se cierra: todo es obligatorio.
            'consecutivo.required' => 'Con estado GESTIONADO el consecutivo es obligatorio.',
            'codigo_actual.required' => 'Con estado GESTIONADO el código actual es obligatorio.',
            'version_actual.required' => 'Con estado GESTIONADO la versión actual es obligatoria.',
            'codigo_estandarizacion.required' => 'Con estado GESTIONADO el código de estandarización es obligatorio.',
            'fecha_estandarizacion.required' => 'Con estado GESTIONADO la fecha de estandarización es obligatoria.',
            'version_actualizada.required' => 'Con estado GESTIONADO la versión actualizada es obligatoria.',
        ];
    }
}
