<?php

namespace App\Http\Requests\Calidad\MtGi11;

use App\Services\Access\MtGi11AccessService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Carga del archivo Excel para el alta masiva de solicitudes MT-GI-11.
 */
class ImportMtGi11Request extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(MtGi11AccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Seleccione el archivo a importar.',
            'import_file.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'import_file.max' => 'El archivo no puede superar 20 MB.',
        ];
    }
}
