<?php

namespace App\Http\Requests\Supplies;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\SupplyRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyRequestCommentRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        /** @var SupplyRequest|null $supplyRequest */
        $supplyRequest = $this->route('supply_request');
        $user = $this->user();

        if (! $supplyRequest instanceof SupplyRequest || $user === null) {
            return false;
        }

        if (! $supplyRequest->puedeComentar()) {
            return false;
        }

        return $user->can('comment', $supplyRequest);
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
