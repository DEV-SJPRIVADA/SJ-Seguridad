<?php

namespace App\Http\Requests\GestionHumana;

use App\Http\Requests\Concerns\UppercasesValidatedText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TerminateEmployeeFichaRequest extends FormRequest
{
    use UppercasesValidatedText;

    public function authorize(): bool
    {
        return $this->user()?->can('ficha_empleados.terminate') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'termination_cause_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('payroll_catalog_items', 'code')->where('catalog_type', 'termination_cause'),
            ],
            'is_rehireable' => ['required', 'boolean'],
            'last_work_day' => ['required', 'date'],
            'termination_date' => ['required', 'date', 'after_or_equal:last_work_day'],
            'termination_notes' => ['nullable', 'string', 'max:1000'],
            // Aprobación del cruce con novedades MT-GH-04 (ya no bloquea, exige confirmar).
            'novedades_conflict_ack' => ['sometimes', 'boolean'],
            'novedades_conflict_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'termination_cause_code' => 'causal de desvinculación',
            'is_rehireable' => 'recontratable',
            'last_work_day' => 'último día de trabajo',
            'termination_date' => 'fecha de desvinculación',
            'termination_notes' => 'observaciones',
            'novedades_conflict_ack' => 'aprobación del cruce con novedades',
            'novedades_conflict_reason' => 'motivo de aprobación',
        ];
    }
}
