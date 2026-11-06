<?php

namespace App\Http\Requests\Calidad\ServicioCliente;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Services\Access\ServicioClienteAccessService;
use Illuminate\Foundation\Http\FormRequest;

class StoreServicioClienteCatalogItemRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ServicioClienteAccessService::class)->canEditParameters($user);
    }

    /**
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['code'];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
