<?php

namespace App\Http\Requests\GestionHumana;

use Illuminate\Foundation\Http\FormRequest;

class ImportEmployeeArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('archivo.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Seleccione un archivo Excel (.xlsx) para importar.',
            'import_file.mimes' => 'El archivo debe ser formato Excel (.xlsx).',
            'import_file.max' => 'El archivo no puede superar 10 MB.',
        ];
    }
}
