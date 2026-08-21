<?php

namespace App\Http\Requests\GestionHumana\PlantillasWord;

use App\Services\GestionHumana\PlantillasWordAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWordDocumentTypeRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('word_document_types', 'code'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'El codigo solo puede contener letras minusculas, numeros y guion bajo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtolower(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
