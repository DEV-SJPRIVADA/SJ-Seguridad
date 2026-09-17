<?php

namespace App\Http\Requests\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use App\Services\Access\DevelopmentRequestAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDevelopmentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(DevelopmentRequestAccessService::class)->canCreate($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mimes = implode(',', config('development-requests.attachments.mimes', ['pdf']));
        $maxKb = (int) config('development-requests.attachments.max_kilobytes', 10240);
        $maxFiles = (int) config('development-requests.attachments.max_files', 10);

        return [
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'area_key' => ['required', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'request_type' => ['required', Rule::in(array_keys(DevelopmentRequest::tiposLabels()))],
            'title' => ['required', 'string', 'max:255'],
            'associated_norm' => ['nullable', 'string', 'max:255'],
            'proceso_sede' => ['nullable', 'string', 'max:255'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_position' => ['nullable', 'string', 'max:255'],
            'requester_email' => ['required', 'email', 'max:255'],
            'requester_phone' => ['nullable', 'string', 'max:50'],
            'leader_id' => ['required', 'integer', 'exists:users,id'],
            'description' => ['required', 'string', 'max:10000'],
            'current_process_problem' => ['required', 'string', 'max:10000'],
            'desired_steps' => ['required', 'string', 'max:10000'],
            'users_description' => ['required', 'string', 'max:5000'],
            'restrictions' => ['required', 'string', 'max:5000'],
            'scope_in' => ['required', 'string', 'max:5000'],
            'scope_out' => ['nullable', 'string', 'max:5000'],
            'acceptance_criteria' => ['required', 'string', 'max:5000'],
            'reports' => ['nullable', 'string', 'max:5000'],
            'suggested_priority' => ['required', Rule::in(array_keys(DevelopmentRequest::prioridadesLabels()))],
            'desired_date' => ['nullable', 'date'],
            'desired_date_justification' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.$maxFiles],
            'attachments.*' => ['file', 'mimes:'.$mimes, 'max:'.$maxKb],
            'attachment_required' => ['nullable', 'array'],
            'attachment_required.*' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'leader_id.required' => 'Seleccione el lider de area que aprueba.',
            'title.required' => 'El nombre del requerimiento es obligatorio.',
            'description.required' => 'La descripcion general es obligatoria.',
            'acceptance_criteria.required' => 'El criterio de aceptacion es obligatorio.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => $this->input('action', 'draft'),
            'title' => trim((string) $this->input('title')),
            'requester_name' => trim((string) $this->input('requester_name')),
            'requester_email' => trim((string) $this->input('requester_email')),
        ]);
    }
}
