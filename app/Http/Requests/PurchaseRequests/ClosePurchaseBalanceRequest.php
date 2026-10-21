<?php

namespace App\Http\Requests\PurchaseRequests;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use Illuminate\Foundation\Http\FormRequest;

class ClosePurchaseBalanceRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        return $this->user()?->can('purchase.tab.processing') ?? false;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:3', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motivo' => 'motivo',
        ];
    }
}
