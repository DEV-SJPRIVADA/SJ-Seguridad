<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($user->hasRole('super-admin') || $user->can('manage.users')) {
            return true;
        }

        return $user->can('purchase.tab.create');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'urgente' => $this->boolean('urgente'),
        ]);
    }

    public function rules(): array
    {
        $maxFiles = (int) config('purchase-requests.attachments.max_files', 5);
        $maxKilobytes = (int) config('purchase-requests.attachments.max_kilobytes', 10240);
        $mimes = implode(',', config('purchase-requests.attachments.mimes', []));

        return [
            'area_key' => ['required', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'fecha_solicitud' => ['required', 'date', 'before_or_equal:today'],
            'solicitud_para' => ['required', 'in:Interno,Cliente'],
            'urgente' => ['boolean'],
            'aprobador_id' => ['required', 'exists:users,id'],
            'proyecto_nuevo' => ['nullable', 'boolean'],
            'razon_social' => ['nullable', 'string', 'max:255', 'required_if:solicitud_para,Cliente'],
            'asume_cliente' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:99999'],
            'items.*.descripcion' => ['required', 'string', 'max:5000'],
            'items.*.referencia' => ['required', 'string', 'max:255'],
            'items.*.utilizacion' => ['required', 'string', 'max:1000'],
            'items.*.ubicacion' => ['required', 'string', 'max:255'],
            'items.*.foto' => ['nullable', 'image', 'max:5120'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'max:'.$maxKilobytes, 'mimes:'.$mimes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $maxFiles = (int) config('purchase-requests.attachments.max_files', 5);
        $maxMb = (int) config('purchase-requests.attachments.max_kilobytes', 10240) / 1024;

        return [
            'attachments.max' => "Puede adjuntar como maximo {$maxFiles} archivos.",
            'attachments.*.file' => 'Cada adjunto debe ser un archivo valido.',
            'attachments.*.max' => "Cada adjunto no puede superar {$maxMb} MB.",
            'attachments.*.mimes' => 'Tipo de archivo no permitido. Use PDF, Word, Excel, PowerPoint, JPG, PNG o WEBP.',
        ];
    }
}
