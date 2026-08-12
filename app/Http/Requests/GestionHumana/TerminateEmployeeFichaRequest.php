<?php

namespace App\Http\Requests\GestionHumana;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TerminateEmployeeFichaRequest extends FormRequest
{
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'termination_cause_code' => 'causal de desvinculacion',
            'is_rehireable' => 'recontratable',
            'last_work_day' => 'ultimo dia de trabajo',
            'termination_date' => 'fecha de desvinculacion',
            'termination_notes' => 'observaciones',
        ];
    }
}
