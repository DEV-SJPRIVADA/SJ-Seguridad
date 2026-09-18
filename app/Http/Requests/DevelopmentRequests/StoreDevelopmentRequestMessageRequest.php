<?php

namespace App\Http\Requests\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDevelopmentRequestMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var DevelopmentRequest|null $developmentRequest */
        $developmentRequest = $this->route('development_request');

        if ($user === null || ! $developmentRequest instanceof DevelopmentRequest) {
            return false;
        }

        if ($developmentRequest->isConversationClosed()) {
            return false;
        }

        return $user->can('comment', $developmentRequest);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
