<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\FormacionAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportFormacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(FormacionAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200'],
            'confirm_replace' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Seleccione un archivo Excel.',
            'import_file.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'import_file.max' => 'El archivo no debe superar 50 MB.',
            'confirm_replace.accepted' => 'Debe confirmar que se borrarán todos los registros actuales.',
        ];
    }
}
