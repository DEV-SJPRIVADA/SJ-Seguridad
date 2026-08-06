<?php

namespace App\Http\Requests\GestionHumana;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeArchiveConsultationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->can('archivo.view') ?? false) || ($user?->can('archivo.manage') ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'received' => ['nullable', 'boolean'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
