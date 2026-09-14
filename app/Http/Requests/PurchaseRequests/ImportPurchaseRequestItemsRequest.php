<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPurchaseRequestItemsRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Seleccione un archivo Excel.',
            'import_file.mimes' => 'El archivo debe ser .xlsx o .xls.',
            'import_file.max' => 'El archivo no puede superar 5 MB.',
        ];
    }
}
