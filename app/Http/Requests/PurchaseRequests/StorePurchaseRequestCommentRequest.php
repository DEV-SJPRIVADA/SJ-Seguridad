<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var PurchaseRequest|null $purchaseRequest */
        $purchaseRequest = $this->route('purchase_request');
        $user = $this->user();

        if (! $purchaseRequest instanceof PurchaseRequest || $user === null) {
            return false;
        }

        // Defensa explícita: Gate::before de super-admin no debe abrir hilos cerrados.
        if (! $purchaseRequest->puedeComentar()) {
            return false;
        }

        return $user->can('comment', $purchaseRequest);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => 'comentario',
        ];
    }
}
