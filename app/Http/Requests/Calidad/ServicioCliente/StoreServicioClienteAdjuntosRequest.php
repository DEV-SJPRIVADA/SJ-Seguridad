<?php

namespace App\Http\Requests\Calidad\ServicioCliente;

use App\Services\Access\ServicioClienteAccessService;
use Illuminate\Foundation\Http\FormRequest;

class StoreServicioClienteAdjuntosRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ServicioClienteAccessService::class)->canEditFoGi22($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxFiles = (int) config('servicio_cliente.adjuntos.max_files_per_upload', 10);
        $maxKilobytes = (int) config('servicio_cliente.adjuntos.max_kilobytes', 10240);
        $mimes = implode(',', config('servicio_cliente.adjuntos.mimes', ['pdf', 'jpg', 'jpeg', 'png']));

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
