<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\DesvinculacionesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ImportHistoricalTerminationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(DesvinculacionesAccessService::class)->canEditSeguimientos($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // extensions (no solo mimes): .xlsm/.xlsx a menudo llegan como octet-stream o zip.
        return [
            'import_file' => ['required', 'file', 'extensions:xlsx,xls,xlsm', 'max:51200'],
            'dry_run' => ['sometimes', 'boolean'],
            'confirm_import' => ['sometimes', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Seleccione el archivo Excel de NOVEDADES.',
            'import_file.file' => 'Seleccione un archivo Excel válido.',
            'import_file.extensions' => 'El archivo debe ser Excel (.xlsx, .xls o .xlsm).',
            'import_file.max' => 'El archivo no debe superar 50 MB.',
            'import_file.uploaded' => 'No se pudo subir el archivo. Revise los límites de PHP (upload_max_filesize / post_max_size).',
            'confirm_import.accepted' => 'Debe confirmar la carga real antes de importar.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('dry_run')) {
            $this->merge([
                'dry_run' => filter_var($this->input('dry_run'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('dry_run')) {
                    return;
                }

                if (! $this->boolean('confirm_import')) {
                    $validator->errors()->add(
                        'confirm_import',
                        'Debe confirmar la carga real antes de importar.',
                    );
                }
            },
        ];
    }

    public function isDryRun(): bool
    {
        return $this->boolean('dry_run');
    }
}
