<?php

namespace App\Http\Requests\GestionHumana\MtSt04;

use App\Services\Access\MtSt04AccessService;
use Illuminate\Foundation\Http\FormRequest;

class ToggleMtSt04RequiresPsicofisicosRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(MtSt04AccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'max:50'],
        ];
    }
}
