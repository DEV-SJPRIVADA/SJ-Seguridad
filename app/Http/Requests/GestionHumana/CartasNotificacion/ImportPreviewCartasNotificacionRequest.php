<?php

namespace App\Http\Requests\GestionHumana\CartasNotificacion;

use App\Services\Access\CartasNotificacionAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ImportPreviewCartasNotificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(CartasNotificacionAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['xlsx', 'xls'])->max(5120),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'archivo Excel',
        ];
    }
}
