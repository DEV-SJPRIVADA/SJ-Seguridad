<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\DesvinculacionesAccessService;
use Illuminate\Foundation\Http\FormRequest;

class LookupBulkTerminationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(DesvinculacionesAccessService::class)->canMasivos($user);
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

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'document_number' => 'cedula',
        ];
    }
}
