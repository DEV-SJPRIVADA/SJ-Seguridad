<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\PurchaseProvider;
use App\Services\Access\PurchaseAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseProviderRequest extends FormRequest
{
    use UppercasesValidatedText;

    /**
     * Solo los usuarios con permiso de edición pueden actualizar proveedores.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PurchaseAccessService::class)->canEditProviders($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $provider = $this->route('purchase_provider');

        return [
            'identificacion' => [
                'required',
                'string',
                'max:50',
                Rule::unique('purchase_providers', 'identificacion')->ignore($provider),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'digits_between:7,15'],
            'correo' => ['nullable', 'email', 'max:255'],
            'estado' => ['required', Rule::in(array_keys(PurchaseProvider::estadoLabels()))],
            'dias_pago' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'ciudad' => [
                'nullable',
                'string',
                'max:120',
                Rule::exists('requisition_cities', 'name')->where('is_active', true),
            ],
            'forma_pago' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'estado' => $this->input('estado', PurchaseProvider::ESTADO_ACTIVO),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['identificacion', 'correo', 'telefono', 'estado', 'ciudad'];
    }
}
