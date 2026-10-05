<?php

namespace App\Http\Requests\GestionHumana\PlantillasWord;

use App\Services\GestionHumana\PlantillasWordAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWordDocumentTemplateRequest extends FormRequest
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
                Rule::exists('word_document_types', 'id'),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'La etiqueta de la plantilla es obligatoria.',
            'word_document_type_id.required' => 'Debe seleccionar un tipo de documento.',
            'word_document_type_id.integer' => 'Debe seleccionar un tipo de documento válido.',
            'word_document_type_id.exists' => 'El tipo de documento seleccionado no existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => trim((string) $this->input('label')),
        ]);
    }
}
