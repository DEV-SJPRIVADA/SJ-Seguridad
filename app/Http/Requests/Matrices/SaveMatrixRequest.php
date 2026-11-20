<?php

namespace App\Http\Requests\Matrices;

use App\Models\Matrices\Matrix;
use App\Services\Matrices\MatrixDefinitionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        $matrix = $this->route('matrix');

        if ($matrix instanceof Matrix) {
            return $this->user()?->can('updateDefinition', $matrix) ?? false;
        }

        return $this->user()?->can('matrices.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $fields = $this->input('fields');

        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            $this->merge(['fields' => is_array($decoded) ? $decoded : []]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'area_key' => ['required', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'fields' => ['required', 'array', 'min:1', 'max:80'],
            'fields.*.field_key' => ['nullable', 'string', 'max:60'],
            'fields.*.label' => ['required', 'string', 'max:160'],
            'fields.*.type' => ['required', 'string', Rule::in(MatrixDefinitionService::V1_TYPES)],
            'fields.*.section' => ['nullable', 'string', 'max:120'],
            'fields.*.help' => ['nullable', 'string', 'max:255'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:160'],
            'fields.*.required' => ['sometimes', 'boolean'],
            'fields.*.col_span' => ['sometimes', 'integer', Rule::in([4, 6, 12])],
            'fields.*.show_in_table' => ['sometimes', 'boolean'],
            'fields.*.filterable' => ['sometimes', 'boolean'],
            'fields.*.preserve_case' => ['sometimes', 'boolean'],
            'fields.*.options' => ['nullable', 'array', 'max:50'],
            'fields.*.options.*.key' => ['nullable', 'string', 'max:60'],
            'fields.*.options.*.label' => ['required_with:fields.*.options', 'string', 'max:160'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre de la matriz',
            'description' => 'descripción',
            'area_key' => 'área',
            'fields' => 'campos',
            'fields.*.label' => 'nombre del campo',
            'fields.*.type' => 'tipo de campo',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function matrixData(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'area_key' => $validated['area_key'],
            'fields' => array_values((array) ($validated['fields'] ?? [])),
        ];
    }
}
