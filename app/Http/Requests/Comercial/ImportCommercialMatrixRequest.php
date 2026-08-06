<?php

namespace App\Http\Requests\Comercial;

use Illuminate\Foundation\Http\FormRequest;

class ImportCommercialMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->can('comercial.matriz.manage')
            || $user->can('manage.users')
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('commercial_matrix.import_max_file_kb', 10240);

        return [
            'import_file' => ['required', 'file', 'mimes:xlsx', 'max:'.$maxKb],
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
