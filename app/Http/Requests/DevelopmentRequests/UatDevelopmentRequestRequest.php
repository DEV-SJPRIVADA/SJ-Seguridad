<?php

namespace App\Http\Requests\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UatDevelopmentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var DevelopmentRequest|null $developmentRequest */
        $developmentRequest = $this->route('development_request');

        if ($user === null || ! $developmentRequest instanceof DevelopmentRequest) {
            return false;
        }

        if ($developmentRequest->status !== DevelopmentRequest::STATUS_EN_PRUEBAS) {
            return false;
        }

        return (int) $developmentRequest->created_by === (int) $user->id
            || $user->hasRole('super-admin')
            || $user->can('manage.users');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'uat_result' => ['required', Rule::in(['si', 'no'])],
            'uat_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
