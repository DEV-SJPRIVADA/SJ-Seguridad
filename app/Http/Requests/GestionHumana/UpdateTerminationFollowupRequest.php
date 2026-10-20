<?php

namespace App\Http\Requests\GestionHumana;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use App\Models\EmployeeTerminationFollowup;
use App\Services\Access\DesvinculacionesAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTerminationFollowupRequest extends FormRequest
{
    use UppercasesValidatedText;

    /**
     * Campos de retiro editables desde el modal de Acciones (sincronizan periodo y Retiros).
     *
     * @var list<string>
     */
    public const RETIRO_FIELDS = ['is_rehireable', 'termination_notes'];

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
        $rules = [
            'payroll_delivered_at' => ['sometimes', 'nullable', 'date'],
            'is_rehireable' => ['sometimes', 'nullable', 'boolean'],
            'termination_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];

        foreach (EmployeeTerminationFollowup::CHECK_FIELDS as $field) {
            $rules[$field] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payroll_delivered_at' => 'fecha entregado nomina',
            'is_rehireable' => 'recontratable',
            'termination_notes' => 'observaciones',
            'check_orden_examenes' => 'orden examenes',
            'check_enviado' => 'enviado',
            'check_control_roll' => 'control roll',
            'check_retiro_arl' => 'retiro arl',
            'check_recibido' => 'recibido',
            'check_paz_y_salvo' => 'paz y salvo',
            'check_reporte_noved' => 'reporte noved',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['payroll_delivered_at', 'is_rehireable'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->exists('termination_notes')) {
            $notes = trim((string) $this->input('termination_notes', ''));
            $this->merge(['termination_notes' => $notes !== '' ? $notes : null]);
        }
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $present = collect($this->editableFields())->filter(fn (string $field): bool => $this->exists($field));

                if ($present->isEmpty()) {
                    $validator->errors()->add(
                        'payload',
                        'Debe enviar al menos un campo editable del seguimiento.',
                    );
                }
            },
        ];
    }

    /**
     * Solo campos editables presentes en el request (PATCH parcial).
     *
     * @return array<string, mixed>
     */
    public function editablePayload(): array
    {
        return collect($this->validated())
            ->only($this->editableFields())
            ->all();
    }

    /**
     * @return list<string>
     */
    private function editableFields(): array
    {
        return [...EmployeeTerminationFollowup::CHECK_FIELDS, 'payroll_delivered_at', ...self::RETIRO_FIELDS];
    }
}
