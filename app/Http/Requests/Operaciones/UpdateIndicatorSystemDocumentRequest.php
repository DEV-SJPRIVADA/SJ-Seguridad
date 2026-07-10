<?php

namespace App\Http\Requests\Operaciones;

use App\Models\IndicatorSystemDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndicatorSystemDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operations.manage') ?? false;
    }

    public function rules(): array
    {
        /** @var IndicatorSystemDocument $document */
        $document = $this->route('document');

        return [
            'slug' => ['required', 'string', 'max:255', Rule::unique('indicator_system_documents', 'slug')->ignore($document->id)],
            'title' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in(['system', 'indicator', 'dashboard'])],
            'indicator_id' => ['nullable', 'integer', 'exists:indicators,id'],
            'is_active' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
