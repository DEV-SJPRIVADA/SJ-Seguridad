<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AttachNotificationTypeEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage.notifications') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
