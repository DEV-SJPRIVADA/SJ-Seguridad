<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Services\Access\AcreditacionesAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class RunAcreditacionValidacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && app(AcreditacionesAccessService::class)->canEdit($user);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        return [
            'fecha_reporte' => ['required', 'date', 'before_or_equal:'.$today],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_reporte.required' => 'La fecha de reporte es obligatoria.',
            'fecha_reporte.date' => 'La fecha de reporte no es válida.',
            'fecha_reporte.before_or_equal' => 'La fecha de reporte no puede ser futura.',
        ];
    }

    public function fechaReporte(): string
    {
        return Carbon::parse((string) $this->validated('fecha_reporte'))
            ->timezone(config('app.timezone'))
            ->toDateString();
    }
}
