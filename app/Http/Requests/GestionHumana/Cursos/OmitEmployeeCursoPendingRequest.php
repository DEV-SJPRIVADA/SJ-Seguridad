<?php

namespace App\Http\Requests\GestionHumana\Cursos;

use App\Services\Access\CursosAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OmitEmployeeCursoPendingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(CursosAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'omit_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'omit_reason.max' => 'El motivo no puede superar 1000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $reason = trim((string) $this->input('omit_reason', ''));

        $this->merge([
            'omit_reason' => $reason === '' ? null : $reason,
        ]);
    }
}
