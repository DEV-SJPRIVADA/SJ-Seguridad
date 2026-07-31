<?php

namespace App\Http\Requests\PurchaseRequests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessSupplyComprasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchase.tab.processing') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:save,complete'],
            'items' => ['required', 'array'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'purchasing_observations' => ['nullable', 'array'],
            'purchasing_observations.*' => ['nullable', 'string', 'max:1000'],
            'quality_observations' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
