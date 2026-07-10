<?php

namespace App\Http\Requests\Operaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicatorSystemDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operations.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('indicator_system_documents', 'slug')],
            'title' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in(['system', 'indicator', 'dashboard'])],
            'indicator_id' => ['nullable', 'integer', 'exists:indicators,id'],
            'is_active' => ['required', 'boolean'],
            'initial_status' => ['required', Rule::in(['draft', 'vigente', 'archivado'])],
            'content' => ['required', 'string'],
            'change_summary' => ['required', 'string', 'max:1000'],
            'change_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
