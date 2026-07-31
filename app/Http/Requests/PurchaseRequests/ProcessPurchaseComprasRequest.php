<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPurchaseComprasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchase.tab.processing') ?? false;
    }

    public function rules(): array
    {
        return [
            'estado_compras' => ['required', Rule::in([
                PurchaseRequest::COMPRAS_PENDIENTE,
                PurchaseRequest::COMPRAS_EN_CURSO,
                PurchaseRequest::COMPRAS_COMPLETADO,
                PurchaseRequest::COMPRAS_RECHAZADO,
            ])],
            'comentarios_compras' => ['nullable', 'string', 'max:5000'],
            'redirect_estado_compras' => ['nullable', 'string'],
        ];
    }
}
