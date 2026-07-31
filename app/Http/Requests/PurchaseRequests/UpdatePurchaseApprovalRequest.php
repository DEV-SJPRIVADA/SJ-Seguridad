<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $purchaseRequest = $this->route('purchase_request');

        return $this->user()?->can('purchase.tab.approval')
            && $purchaseRequest instanceof PurchaseRequest
            && (int) $purchaseRequest->aprobador_id === (int) $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in([PurchaseRequest::ESTADO_APROBADO, PurchaseRequest::ESTADO_RECHAZADO])],
            'comentarios_director' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
