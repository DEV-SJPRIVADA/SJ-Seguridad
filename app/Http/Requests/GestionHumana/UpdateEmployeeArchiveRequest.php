<?php

namespace App\Http\Requests\GestionHumana;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeArchiveRequest extends FormRequest
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
            'archive_shelf' => ['nullable', 'string', 'max:100'],
            'archive_box' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'archive_shelf' => 'estantes',
            'archive_box' => 'cajas',
        ];
    }
}
