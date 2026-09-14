<?php

namespace Database\Factories;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeTerminationFollowup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeTerminationFollowup>
 *
 * Requiere `personal_requisition_ficha_entry_id` y `employee_ficha_employment_period_id`
 * (usar {@see forPeriod()} o pasarlos en create()).
 */
class EmployeeTerminationFollowupFactory extends Factory
{
    protected $model = EmployeeTerminationFollowup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_number' => fake()->numerify('##########'),
            'full_name' => fake()->name(),
            'position_name' => 'Vigilante',
            'termination_cause_code' => 'RENUNCIA',
            'termination_cause_name' => 'Renuncia voluntaria',
            'is_rehireable' => true,
            'termination_notes' => null,
            'termination_date' => now()->toDateString(),
            'registered_at' => now(),
            'letter_generated' => false,
            'check_orden_examenes' => false,
            'check_enviado' => false,
            'check_control_roll' => false,
            'check_retiro_arl' => false,
            'check_retiro_cesantias' => false,
            'check_recibido' => false,
            'check_paz_y_salvo' => false,
            'check_reporte_noved' => false,
            'payroll_delivered_at' => null,
            'created_by' => null,
        ];
    }

    public function letterGenerated(bool $value = true): static
    {
        return $this->state(fn (): array => [
            'letter_generated' => $value,
        ]);
    }

    public function okTodo(): static
    {
        return $this->state(fn (): array => [
            'check_orden_examenes' => true,
            'check_enviado' => true,
            'check_control_roll' => true,
            'check_retiro_arl' => true,
            'check_retiro_cesantias' => true,
            'check_recibido' => true,
            'check_paz_y_salvo' => true,
            'check_reporte_noved' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function forPeriod(EmployeeFichaEmploymentPeriod $period, array $attributes = []): static
    {
        return $this->state(fn (): array => array_merge([
            'personal_requisition_ficha_entry_id' => $period->personal_requisition_ficha_entry_id,
            'employee_ficha_employment_period_id' => $period->id,
            'position_name' => $period->position_name,
            'termination_cause_code' => $period->termination_cause_code,
            'termination_cause_name' => $period->termination_cause_name,
            'is_rehireable' => $period->is_rehireable,
            'termination_notes' => $period->termination_notes,
            'termination_date' => $period->termination_date?->toDateString() ?? $period->last_work_day?->toDateString(),
        ], $attributes));
    }
}
