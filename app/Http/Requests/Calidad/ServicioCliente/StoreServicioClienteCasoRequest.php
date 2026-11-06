<?php

namespace App\Http\Requests\Calidad\ServicioCliente;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\ServicioClienteCaso;
use App\Services\Access\ServicioClienteAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServicioClienteCasoRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ServicioClienteAccessService::class)->canEditFoGi22($user);
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['tipo_cliente', 'eficacia'];
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
            'fecha_recepcion' => ['required', 'date'],
            'fecha_verificacion' => ['nullable', 'date'],
            'tipo_cliente' => ['required', Rule::in(array_keys(config('servicio_cliente.tipos_cliente', [])))],
            'tipologia_id' => ['required', 'integer', 'exists:servicio_cliente_tipologias,id'],
            'fuente_id' => ['required', 'integer', 'exists:servicio_cliente_fuentes,id'],
            'commercial_client_id' => [
                Rule::requiredIf(fn (): bool => $this->input('tipo_cliente') === ServicioClienteCaso::TIPO_EXTERNO),
                'nullable',
                'integer',
                'exists:commercial_clients,id',
            ],
            'city_id' => ['required', 'integer', 'exists:requisition_cities,id'],
            'descripcion' => ['required', 'string', 'max:5000'],
            'categoria_id' => ['required', 'integer', 'exists:servicio_cliente_categorias,id'],
            'correccion' => ['required', 'string', 'max:5000'],
            'accion_realizar' => ['required', 'string', 'max:5000'],
            'responsable_id' => ['required', 'integer', 'exists:servicio_cliente_responsables,id'],
            'responsable_seguimiento' => ['nullable', 'string', 'max:2000'],
            'eficacia' => ['required', Rule::in(array_keys(config('servicio_cliente.eficacia', [])))],
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
            'commercial_client_id.required' => 'Seleccione el cliente.',
        ];
    }
}
