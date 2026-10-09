<?php

namespace App\Http\Requests\GestionHumana\MtSt04;

use App\Services\Access\MtSt04AccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportMtSt04Request extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(MtSt04AccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
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
        ];
    }
}
