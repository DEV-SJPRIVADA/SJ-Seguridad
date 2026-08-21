<?php

namespace App\Http\Requests\GestionHumana\PlantillasWord;

use App\Services\GestionHumana\PlantillasWordAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWordDocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(PlantillasWordAccessService::class)->canManage($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'word_document_type_id' => [
                'required',
                'integer',
                Rule::exists('word_document_types', 'id')->where('is_active', true),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'template' => ['required', 'file', 'mimes:docx', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta de la plantilla es obligatoria.',
            'word_document_type_id.required' => 'Debe seleccionar un tipo de documento activo.',
            'word_document_type_id.exists' => 'El tipo de documento debe existir y estar activo.',
            'template.required' => 'Debe seleccionar un archivo Word (.docx).',
            'template.mimes' => 'La plantilla debe ser un archivo .docx.',
            'template.max' => 'La plantilla no puede superar 5 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => trim((string) $this->input('label')),
        ]);
    }
}
