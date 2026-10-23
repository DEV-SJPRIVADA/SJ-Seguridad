<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\PurchaseTaxRate;
use App\Services\Access\PurchaseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta / edición de una tasa de IVA (Catálogos → IVA). Mismo permiso de edición que Proveedores.
 */
class SavePurchaseTaxRateRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PurchaseAccessService::class)->canEditProviders($user);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rate = $this->route('purchase_tax_rate');
        $ignoreId = $rate instanceof PurchaseTaxRate ? $rate->id : null;

        return [
            'nombre' => ['required', 'string', 'max:60'],
            'porcentaje' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                Rule::unique('purchase_tax_rates', 'porcentaje')->ignore($ignoreId),
            ],
            'activo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'porcentaje.unique' => 'Ya existe una tasa con ese porcentaje.',
        ];
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['porcentaje', 'activo'];
    }
}
