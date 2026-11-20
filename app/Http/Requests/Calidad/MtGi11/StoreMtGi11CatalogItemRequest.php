<?php

namespace App\Http\Requests\Calidad\MtGi11;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Services\Access\MtGi11AccessService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta y edición de ítems de los catálogos MT-GI-11.
 *
 * `color` solo se usa en el catálogo de estados; el resto de catálogos lo ignora.
 */
class StoreMtGi11CatalogItemRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(MtGi11AccessService::class)->canEditParameters($user);
    }

    /**
     * `color` es una clave técnica de estilo (status-pill), no texto de negocio.
     *
     * @return list<string>
     */
    protected function uppercaseExceptKeys(): array
    {
        return ['color'];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'color' => ['nullable', 'string', 'in:success,warning,danger,info,muted'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
