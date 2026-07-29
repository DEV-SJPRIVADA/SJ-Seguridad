<?php

namespace App\Http\Requests\Comercial;

use App\Support\CommercialDocumentCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommercialClientChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $statusValues = CommercialDocumentCatalog::documentStatusValues();
        $documentRules = [];

        foreach (CommercialDocumentCatalog::documentKeys() as $key) {
            $documentRules["documents.{$key}"] = ['nullable', 'string', Rule::in($statusValues)];
        }

        return [
            'documentation_expires_on' => ['nullable', 'date'],
            'alert_days_before' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'documents' => ['nullable', 'array'],
            ...$documentRules,
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('documentation_expires_on') && $this->input('documentation_expires_on') === '') {
            $this->merge(['documentation_expires_on' => null]);
        }

        if ($this->has('alert_days_before') && $this->input('alert_days_before') === '') {
            $this->merge(['alert_days_before' => null]);
        }
    }
}
