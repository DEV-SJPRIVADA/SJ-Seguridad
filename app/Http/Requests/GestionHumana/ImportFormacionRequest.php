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
        // extensions (no solo mimes): en Windows/Hostinger un .xlsx real a menudo llega
        // como application/octet-stream o application/zip y falla mimes:xlsx.
        return [
            'import_file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:51200'],
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
            'import_file.file' => 'Seleccione un archivo Excel válido.',
            'import_file.extensions' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'import_file.max' => 'El archivo no debe superar 50 MB.',
            'import_file.uploaded' => 'No se pudo subir el archivo. Suele deberse al límite de PHP (upload_max_filesize / post_max_size). Pruebe un archivo más liviano o suba el tope del servidor.',
            'confirm_replace.accepted' => 'Debe confirmar que se borrarán todos los registros actuales.',
        ];
    }
}
