<?php

namespace App\Http\Requests\Requisitions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideManagementApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('requisitions.approve.management') ?? false;
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
