<?php

namespace App\Http\Requests\Requisitions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncRequisitionNotificationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage.requisition.parameters') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type_slug' => [
                'required',
                'string',
                Rule::exists('requisition_notification_types', 'slug'),
            ],
            'email_ids' => ['nullable', 'array'],
            'email_ids.*' => ['integer', Rule::exists('requisition_notification_emails', 'id')],
        ];
    }
}
