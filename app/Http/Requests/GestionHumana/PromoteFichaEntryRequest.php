<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\FichaEmpleadosAccessService;
use Illuminate\Foundation\Http\FormRequest;

class PromoteFichaEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(FichaEmpleadosAccessService::class)->canManage($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
