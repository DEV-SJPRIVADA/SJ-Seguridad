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
        return [
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'root_process' => ['required', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'document_type' => ['required', 'string', Rule::in(array_keys(config('access.quality_document_types', [])))],
            'description' => ['nullable', 'string', 'max:2000'],
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
}
