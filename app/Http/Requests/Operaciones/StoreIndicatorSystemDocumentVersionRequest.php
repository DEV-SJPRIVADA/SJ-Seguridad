<?php

namespace App\Http\Requests\Operaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicatorSystemDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('operations.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['draft', 'vigente', 'archivado'])],
            'content' => ['required', 'string'],
            'change_summary' => ['required', 'string', 'max:1000'],
            'change_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
