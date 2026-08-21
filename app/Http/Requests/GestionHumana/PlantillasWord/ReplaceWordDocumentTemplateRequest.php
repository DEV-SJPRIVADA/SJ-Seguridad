<?php

namespace App\Http\Requests\GestionHumana\PlantillasWord;

use App\Services\GestionHumana\PlantillasWordAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceWordDocumentTemplateRequest extends FormRequest
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
