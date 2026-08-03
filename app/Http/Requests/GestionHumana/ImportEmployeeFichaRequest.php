<?php

namespace App\Http\Requests\GestionHumana;

use Illuminate\Foundation\Http\FormRequest;

class ImportEmployeeFichaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('ficha_empleados.manage') ?? false;
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
}
