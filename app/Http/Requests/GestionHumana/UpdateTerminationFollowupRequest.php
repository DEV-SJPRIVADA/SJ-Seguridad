<?php

namespace App\Http\Requests\GestionHumana;

use App\Models\EmployeeTerminationFollowup;
use App\Services\Access\DesvinculacionesAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTerminationFollowupRequest extends FormRequest
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
        $rules = [
            'payroll_delivered_at' => ['sometimes', 'nullable', 'date'],
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
            'check_orden_examenes' => 'orden examenes',
            'check_enviado' => 'enviado',
            'check_control_roll' => 'control roll',
            'check_retiro_arl' => 'retiro arl',
            'check_retiro_cesantias' => 'retiro cesantias',
            'check_recibido' => 'recibido',
            'check_paz_y_salvo' => 'paz y salvo',
            'check_reporte_noved' => 'reporte noved',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('payroll_delivered_at') && $this->input('payroll_delivered_at') === '') {
            $this->merge(['payroll_delivered_at' => null]);
        }
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $allowed = array_merge(EmployeeTerminationFollowup::CHECK_FIELDS, ['payroll_delivered_at']);
                $present = collect($allowed)->filter(fn (string $field): bool => $this->exists($field));

                if ($present->isEmpty()) {
                    $validator->errors()->add(
                        'payload',
                        'Debe enviar al menos un check o la fecha entregado nomina.',
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
        $allowed = array_merge(EmployeeTerminationFollowup::CHECK_FIELDS, ['payroll_delivered_at']);

        return collect($this->validated())
            ->only($allowed)
            ->all();
    }
}
