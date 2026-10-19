<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Services\Access\PurchaseAccessService;
use Illuminate\Foundation\Http\FormRequest;

class ImportPurchaseProvidersRequest extends FormRequest
{
    /**
     * Solo los usuarios con permiso de edición pueden importar proveedores.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PurchaseAccessService::class)->canEditProviders($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKilobytes = (int) config('purchase-requests.providers_import_max_kilobytes', 20480);

        return [
            'import_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:'.$maxKilobytes],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Seleccione el archivo VINE01.',
            'import_file.mimes' => 'El archivo debe ser .xlsx, .xls o .csv.',
            'import_file.max' => 'El archivo supera el tamaño máximo permitido.',
        ];
    }
}
