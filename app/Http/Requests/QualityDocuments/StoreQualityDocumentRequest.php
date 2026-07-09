<?php

namespace App\Http\Requests\QualityDocuments;

use App\Models\QualityDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreQualityDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage.quality.documents') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->metadataRules() + [
            'type' => ['required', Rule::in([QualityDocument::TYPE_FILE, QualityDocument::TYPE_LINK])],
            'file' => [
                Rule::requiredIf(fn () => $this->input('type') === QualityDocument::TYPE_FILE),
                'nullable',
                'file',
                'max:10240',
                'mimes:doc,docx,xls,xlsx',
            ],
            'external_url' => [
                Rule::requiredIf(fn () => $this->input('type') === QualityDocument::TYPE_LINK),
                'nullable',
                'url',
                'max:2048',
            ],
            'areas' => ['nullable', 'array'],
            'areas.*' => ['string', Rule::in(array_keys(config('access.areas', [])))],
            'users' => ['nullable', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $areas = collect($this->input('areas', []))->filter()->values();
            $users = collect($this->input('users', []))->filter()->values();

            if ($areas->isEmpty() && $users->isEmpty()) {
                $validator->errors()->add('areas', 'Asigna al menos un area o un usuario.');
            }

            if ($this->input('type') !== QualityDocument::TYPE_FILE || ! $this->hasFile('file')) {
                return;
            }

            $extension = strtolower($this->file('file')->getClientOriginalExtension());
            $allowed = ['doc', 'docx', 'xls', 'xlsx'];

            if (! in_array($extension, $allowed, true)) {
                $validator->errors()->add('file', 'El archivo debe ser Word o Excel (.doc, .docx, .xls, .xlsx).');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function metadataRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'process_key' => ['required', 'string', Rule::in(array_keys(config('quality-documents.processes', [])))],
            'document_type' => ['required', 'string', Rule::in(array_keys(config('quality-documents.types', [])))],
            'origin' => ['required', 'string', Rule::in(array_keys(config('quality-documents.origins', [])))],
            'document_status' => ['required', 'string', Rule::in(array_keys(config('quality-documents.document_statuses', [])))],
            'activity_status' => ['required', 'string', Rule::in(array_keys(config('quality-documents.activity_statuses', [])))],
            'storage_type' => ['required', 'string', Rule::in(array_keys(config('quality-documents.storage_types', [])))],
            'current_version' => ['nullable', 'string', 'max:50'],
            'last_updated_at' => ['nullable', 'date'],
            'retention_period' => ['nullable', 'string', 'max:255'],
            'final_disposition' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
