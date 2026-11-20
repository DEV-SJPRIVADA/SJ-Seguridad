<?php

namespace App\Http\Requests\Matrices;

use App\Models\Matrices\Matrix;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        $matrix = $this->route('matrix');

        if (! $matrix instanceof Matrix) {
            return false;
        }

        return $this->user()?->can('share', $matrix) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $kind = $this->input('kind');

        return [
            'kind' => ['required', Rule::in(['user', 'area'])],
            'user_id' => [
                Rule::requiredIf(fn (): bool => $kind === 'user'),
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'area_key' => [
                Rule::requiredIf(fn (): bool => $kind === 'area'),
                'nullable',
                'string',
                Rule::in(array_keys(config('access.areas', []))),
            ],
            'can_register' => ['sometimes', 'boolean'],
            'can_view_others' => ['sometimes', 'boolean'],
            'can_edit_others' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'usuario',
            'area_key' => 'área',
            'expires_at' => 'vigencia',
        ];
    }
}
