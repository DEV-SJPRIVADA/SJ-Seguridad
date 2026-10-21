<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Compras devuelve un pedido al solicitante con motivo obligatorio (se guarda en mayúsculas).
 */
class ReturnToRequesterRequest extends FormRequest
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
            'motivo_devolucion' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motivo_devolucion' => 'motivo de la devolución',
        ];
    }
}
