<?php

namespace App\Http\Requests\GestionHumana\Acreditaciones;

use App\Services\Access\AcreditacionesAccessService;
use App\Services\GestionHumana\AcreditacionValidacionesResultStore;
use App\Services\GestionHumana\AcreditacionValidacionesRowFilter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AcreditacionValidacionesExportRequest extends FormRequest
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
        $requiresCola = $this->routeIs('gestion-humana.acreditaciones.validaciones.export');

        return [
            'fecha_reporte' => ['required', 'date', 'before_or_equal:'.$today],
            'run_token' => ['required', 'uuid'],
            'cola' => [
                Rule::requiredIf($requiresCola),
                'nullable',
                'string',
                Rule::in(AcreditacionValidacionesResultStore::COLAS),
            ],
            'document_number' => ['nullable', 'string', 'max:50'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:150'],
            'cargo_apo' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'string', 'max:50'],
            'personal_tipo' => ['nullable', 'string', Rule::in(['', 'OPERATIVO', 'ADMINISTRATIVO'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_reporte.required' => 'La fecha de reporte es obligatoria.',
            'run_token.required' => 'Ejecute validaciones primero.',
            'run_token.uuid' => 'El token de corrida no es válido.',
            'cola.required' => 'La cola es obligatoria.',
            'cola.in' => 'La cola de validación no es válida.',
        ];
    }

    public function fechaReporte(): string
    {
        return Carbon::parse((string) $this->validated('fecha_reporte'))
            ->timezone(config('app.timezone'))
            ->toDateString();
    }

    public function runToken(): string
    {
        return (string) $this->validated('run_token');
    }

    public function cola(): string
    {
        return (string) ($this->validated('cola') ?? '');
    }

    /**
     * @return array<string, string>
     */
    public function filters(): array
    {
        return app(AcreditacionValidacionesRowFilter::class)->filtersFromRequest($this);
    }
}
