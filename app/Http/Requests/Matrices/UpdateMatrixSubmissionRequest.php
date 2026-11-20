<?php

namespace App\Http\Requests\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixSubmission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMatrixSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $matrix = $this->route('matrix');
        $submission = $this->route('submission');

        if (! $matrix instanceof Matrix || ! $submission instanceof MatrixSubmission) {
            return false;
        }

        return $this->user()?->can('updateSubmission', [$matrix, $submission]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'values' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'array', 'max:10'],
            'files.*.*' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt,zip,rar'],
            'signature_mode' => ['nullable', 'array'],
            'signature_mode.*' => ['nullable', 'in:draw,profile'],
            'signature_canvas' => ['nullable', 'array'],
            'signature_canvas.*' => ['nullable', 'string'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'files.*.*' => 'archivo adjunto',
            'assigned_to_user_id' => 'usuario asignado',
        ];
    }
}
