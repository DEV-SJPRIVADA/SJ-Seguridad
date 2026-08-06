<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage.users') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document_number' => ['required', 'string', 'max:50', Rule::unique('users', 'document_number')],
            'area_key' => ['nullable', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'sede_id' => ['nullable', 'integer', Rule::exists('supply_sites', 'id')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.required' => 'La cedula es obligatoria.',
            'document_number.unique' => 'Esa cedula ya esta registrada por otro usuario.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'document_number' => trim((string) $this->input('document_number', '')),
            'area_key' => blank($this->input('area_key')) ? null : $this->string('area_key')->toString(),
            'sede_id' => blank($this->input('sede_id')) ? null : $this->integer('sede_id'),
            'is_active' => $this->boolean('is_active'),
            'must_change_password' => $this->boolean('must_change_password', true),
        ]);
    }
}
