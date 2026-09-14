<?php

namespace App\Http\Requests\GestionHumana;

use App\Services\Access\DesvinculacionesAccessService;
use Illuminate\Foundation\Http\FormRequest;

class RevertTerminationFollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(DesvinculacionesAccessService::class)->canEditSeguimientos($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => 'motivo',
        ];
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
