<?php

namespace App\Http\Requests\GestionHumana\ClienteInterno;

use App\Services\Access\ClienteInternoAccessService;
use App\Services\GestionHumana\ClienteInternoCatalogService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteInternoCatalogItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ClienteInternoAccessService::class)->canEditParameters($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = (string) $this->route('type');
        $catalogService = app(ClienteInternoCatalogService::class);
        $table = $catalogService->tableNameFor($type);
        $nameMax = $type === ClienteInternoCatalogService::TYPE_ESTADOS ? 100 : 150;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique($table, 'code'),
            ],
            'name' => ['required', 'string', 'max:'.$nameMax],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        abort_unless(
            app(ClienteInternoCatalogService::class)->isManagedType((string) $this->route('type')),
            404
        );

        $this->merge([
            'code' => trim((string) $this->input('code')),
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
