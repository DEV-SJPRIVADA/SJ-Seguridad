<?php

namespace App\Http\Requests\GestionHumana\ReportesNovedades;

use App\Services\Access\ReportesNovedadesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LookupFichaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ReportesNovedadesAccessService::class)->canEditAnySheet($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_number' => trim((string) $this->input('document_number')),
        ]);
    }
}
