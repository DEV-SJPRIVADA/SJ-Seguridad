<?php

namespace App\Http\Requests\Calidad\MtGi11;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Services\Access\MtGi11AccessService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de adjuntos sueltos sobre una solicitud existente.
 */
class StoreMtGi11AdjuntosRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();
        $solicitud = $this->route('solicitud');

        if ($user === null || $solicitud === null) {
            return false;
        }

        return app(MtGi11AccessService::class)->canManageSolicitud($user, (int) $solicitud->created_by);
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
            'attachments' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'max:'.$maxKilobytes, 'mimes:'.$mimes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.required' => 'Seleccione al menos un archivo.',
            'attachments.*.mimes' => 'Tipo de archivo no permitido. Use PDF, JPG o PNG.',
            'attachments.*.max' => 'Cada adjunto no puede superar 10 MB.',
        ];
    }
}
