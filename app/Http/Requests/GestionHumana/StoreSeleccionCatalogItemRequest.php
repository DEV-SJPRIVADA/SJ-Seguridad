<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\SeleccionAccessService;
use App\Services\GestionHumana\SeleccionCatalogService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeleccionCatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(SeleccionAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = (string) $this->route('type');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payroll_catalog_items', 'code')->where('catalog_type', $type),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        abort_unless(
            app(SeleccionCatalogService::class)->isManagedType((string) $this->route('type')),
            404
        );

        $this->merge([
            'code' => trim((string) $this->input('code')),
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
