<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\FormacionAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'mode' => ['required', Rule::in(['all', 'period'])],
            'anio' => ['required_if:mode,period', 'nullable', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required_if:mode,period', 'nullable', 'integer', 'min:1', 'max:12'],
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
            'mode.required' => 'Seleccione el tipo de importación.',
            'mode.in' => 'El tipo de importación no es válido.',
            'anio.required_if' => 'Indique el año del mes a reemplazar.',
            'mes.required_if' => 'Indique el mes a reemplazar.',
            'confirm_replace.accepted' => 'Debe confirmar el reemplazo antes de importar.',
        ];
    }
}
