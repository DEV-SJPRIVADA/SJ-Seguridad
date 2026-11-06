<?php

namespace App\Http\Requests\GestionHumana\ReportesNovedades;

use App\Services\Access\ReportesNovedadesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIncapacidadAdjuntosRequest extends FormRequest
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
        $maxFiles = (int) config('reportes_novedades.incapacidades_adjuntos.max_files_per_upload', 10);
        $maxKilobytes = (int) config('reportes_novedades.incapacidades_adjuntos.max_kilobytes', 10240);
        $mimes = implode(',', config('reportes_novedades.incapacidades_adjuntos.mimes', ['pdf', 'jpg', 'jpeg']));

        return [
            'attachments' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'max:'.$maxKilobytes, 'mimes:'.$mimes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxFiles = (int) config('reportes_novedades.incapacidades_adjuntos.max_files_per_upload', 10);
        $maxMb = (int) config('reportes_novedades.incapacidades_adjuntos.max_kilobytes', 10240) / 1024;

        return [
            'attachments.required' => 'Seleccione al menos un archivo.',
            'attachments.min' => 'Seleccione al menos un archivo.',
            'attachments.max' => "Puede adjuntar como máximo {$maxFiles} archivos a la vez.",
            'attachments.*.file' => 'Cada adjunto debe ser un archivo válido.',
            'attachments.*.max' => "Cada adjunto no puede superar {$maxMb} MB.",
            'attachments.*.mimes' => 'Tipo de archivo no permitido. Use PDF o JPG.',
        ];
    }
}
