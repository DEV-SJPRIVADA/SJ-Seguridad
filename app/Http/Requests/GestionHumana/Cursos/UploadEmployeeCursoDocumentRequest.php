<?php

namespace App\Http\Requests\GestionHumana\Cursos;

use App\Services\Access\CursosAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadEmployeeCursoDocumentRequest extends FormRequest
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
        $mimes = implode(',', config('cursos.document.mimes', ['pdf', 'jpg', 'jpeg', 'png', 'webp']));
        $maxKb = (int) config('cursos.document.max_kilobytes', 10240);

        return [
            'document' => ['required', 'file', 'mimes:'.$mimes, 'max:'.$maxKb],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Seleccione un archivo.',
            'document.mimes' => 'El documento debe ser PDF, JPG, PNG o WEBP.',
            'document.max' => 'El documento no puede superar 10 MB.',
        ];
    }
}
