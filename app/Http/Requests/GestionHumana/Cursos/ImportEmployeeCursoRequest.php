<?php

namespace App\Http\Requests\GestionHumana\Cursos;

use App\Services\Access\CursosAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportEmployeeCursoRequest extends FormRequest
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
