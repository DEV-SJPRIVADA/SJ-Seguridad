<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\FichaEmpleadosAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateContratacionLettersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(FichaEmpleadosAccessService::class)->canManage($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'template_ids' => ['required', 'array', 'min:1'],
            'template_ids.*' => ['required', 'integer', 'distinct', 'exists:termination_letter_document_templates,id'],
            'signatory_id' => ['required', 'integer', 'exists:payroll_catalog_items,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'template_ids.required' => 'Debe seleccionar al menos una plantilla.',
            'template_ids.min' => 'Debe seleccionar al menos una plantilla.',
            'template_ids.*.exists' => 'Una o mas plantillas seleccionadas no existen.',
            'signatory_id.required' => 'Debe seleccionar un firmante.',
            'signatory_id.exists' => 'El firmante seleccionado no es valido.',
        ];
    }

    /**
     * @return list<int>
     */
    public function templateIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->validated('template_ids');

        return array_values(array_map(static fn (int|string $id): int => (int) $id, $ids));
    }

    public function signatoryId(): int
    {
        return (int) $this->validated('signatory_id');
    }
}
