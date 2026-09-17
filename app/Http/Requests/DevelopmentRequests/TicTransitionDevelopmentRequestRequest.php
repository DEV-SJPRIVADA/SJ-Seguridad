<?php

namespace App\Http\Requests\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use App\Services\Access\DevelopmentRequestAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicTransitionDevelopmentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(DevelopmentRequestAccessService::class)->canProcessTic($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'to_status' => ['required', 'string'],
            'comment' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('to_status'), [
                    DevelopmentRequest::STATUS_DEVUELTO,
                    DevelopmentRequest::STATUS_RECHAZADO,
                ], true)),
                'nullable',
                'string',
                'max:5000',
            ],
            'assigned_programmer_id' => ['nullable', 'integer', 'exists:users,id'],
            'tic_viability' => ['nullable', Rule::in(['aceptado', 'devuelto', 'rechazado'])],
            'tic_confirmed_priority' => ['nullable', Rule::in(array_keys(DevelopmentRequest::prioridadesLabels()))],
            'tic_complexity' => ['nullable', Rule::in(['baja', 'media', 'alta'])],
            'requires_fo_ge_12' => ['nullable', 'boolean'],
            'requires_extended_analysis' => ['nullable', 'boolean'],
            'tic_estimated_date' => ['nullable', 'date'],
            'tic_risks' => ['nullable', 'string', 'max:5000'],
            'tic_analysis_notes' => ['nullable', 'string', 'max:5000'],
            'closure_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_fo_ge_12' => $this->boolean('requires_fo_ge_12'),
            'requires_extended_analysis' => $this->boolean('requires_extended_analysis'),
        ]);
    }
}
