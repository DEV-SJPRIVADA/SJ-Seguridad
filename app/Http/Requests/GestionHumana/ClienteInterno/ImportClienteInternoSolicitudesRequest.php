<?php

namespace App\Http\Requests\GestionHumana\ClienteInterno;

use App\Services\Access\ClienteInternoAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportClienteInternoSolicitudesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(ClienteInternoAccessService::class)->canEditSolicitudes($user);
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
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
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
            'anio.required' => 'Seleccione el año del periodo a reemplazar.',
            'mes.required' => 'Seleccione el mes del periodo a reemplazar.',
            'confirm_replace.accepted' => 'Debe confirmar que se borrarán las solicitudes del periodo seleccionado.',
        ];
    }
}
