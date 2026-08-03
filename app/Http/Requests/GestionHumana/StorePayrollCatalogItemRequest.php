<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\GestionHumana\EmployeeFichaCatalogService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollCatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('ficha_empleados.manage') ?? false;
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
        $this->merge([
            'code' => trim((string) $this->input('code')),
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $type = (string) $this->route('type');

            if (! app(EmployeeFichaCatalogService::class)->isValidType($type)) {
                $validator->errors()->add('type', 'Tipo de catalogo no valido.');
            }
        });
    }
}
