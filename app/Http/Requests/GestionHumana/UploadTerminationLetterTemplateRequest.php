<?php

namespace App\Http\Requests\GestionHumana;

use Illuminate\Foundation\Http\FormRequest;

class UploadTerminationLetterTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template' => ['required', 'file', 'mimes:docx', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template.required' => 'Debe seleccionar un archivo Word (.docx).',
            'template.mimes' => 'La plantilla debe ser un archivo .docx.',
            'template.max' => 'La plantilla no puede superar 5 MB.',
        ];
    }
}
