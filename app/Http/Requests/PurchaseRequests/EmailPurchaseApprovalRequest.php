<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailPurchaseApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->hasValidSignature()) {
            return false;
        }

        $purchaseRequest = $this->route('purchase_request');

        if (! $purchaseRequest instanceof PurchaseRequest) {
            return false;
        }

        $directorId = (int) $this->query('director', $this->input('director'));

        return $directorId > 0 && (int) $purchaseRequest->aprobador_id === $directorId;
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
