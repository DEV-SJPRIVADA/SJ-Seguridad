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

        return $purchaseRequest instanceof PurchaseRequest
            && $this->user()?->can('approve', $purchaseRequest);
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in([PurchaseRequest::ESTADO_APROBADO, PurchaseRequest::ESTADO_RECHAZADO])],
            'comentarios_director' => [
                Rule::requiredIf(fn (): bool => $this->input('estado') === PurchaseRequest::ESTADO_RECHAZADO),
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
