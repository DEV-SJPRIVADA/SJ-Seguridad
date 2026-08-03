<?php

namespace App\Http\Requests\Comercial;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommercialParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = (string) $this->route('type');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($type === 'portfolios') {
            $rules['slug'] = [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('commercial_portfolios', 'slug'),
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
