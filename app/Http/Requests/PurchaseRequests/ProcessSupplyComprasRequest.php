<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Observaciones de Compras por línea de suministro. No pide precio ni cambia estado:
 * el precio vive en la OC y el estado lo escribe la máquina.
 */
class ProcessSupplyComprasRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        return $this->user()?->can('purchase.tab.processing') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'purchasing_observations' => ['nullable', 'array'],
            'purchasing_observations.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
