<?php

namespace App\Http\Requests\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use App\Services\Access\DevelopmentRequestAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaderDecisionDevelopmentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var DevelopmentRequest|null $developmentRequest */
        $developmentRequest = $this->route('development_request');

        if ($user === null || ! $developmentRequest instanceof DevelopmentRequest) {
            return false;
        }

        $access = app(DevelopmentRequestAccessService::class);
        if (! $access->canApproveLeader($user)) {
            return false;
        }

        return $access->isAdminBypass($user)
            || (int) $developmentRequest->leader_id === (int) $user->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'notes' => [
                Rule::requiredIf(fn (): bool => $this->input('decision') === 'reject'),
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notes.required' => 'Indique el motivo del rechazo.',
        ];
    }
}
