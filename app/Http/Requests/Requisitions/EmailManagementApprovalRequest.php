<?php

namespace App\Http\Requests\Requisitions;

use App\Models\PersonalRequisition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailManagementApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->hasValidSignature()) {
            return false;
        }

        $requisition = $this->route('requisition');

        return $requisition instanceof PersonalRequisition;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'comment' => [
                Rule::requiredIf(fn (): bool => $this->input('action') === 'reject'),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
