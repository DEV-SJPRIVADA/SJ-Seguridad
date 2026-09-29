<?php

namespace Database\Factories;

use App\Models\EmployeeAcreditacionPending;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAcreditacionPending>
 */
class EmployeeAcreditacionPendingFactory extends Factory
{
    protected $model = EmployeeAcreditacionPending::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_number' => fake()->numerify('##########'),
            'full_name' => fake()->name(),
            'employee_ficha_profile_id' => null,
            'personal_requisition_ficha_entry_id' => null,
            'status' => EmployeeAcreditacionPending::STATUS_PENDING,
            'enqueued_at' => now(),
            'enqueued_by' => null,
            'omitted_at' => null,
            'omitted_by' => null,
            'omit_reason' => null,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolved_via' => null,
            'acreditacion_acreditado_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeAcreditacionPending::STATUS_PENDING,
            'omitted_at' => null,
            'omitted_by' => null,
            'omit_reason' => null,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolved_via' => null,
            'acreditacion_acreditado_id' => null,
        ]);
    }

    public function omitted(?string $reason = null): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeAcreditacionPending::STATUS_OMITTED,
            'omitted_at' => now(),
            'omit_reason' => $reason,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolved_via' => null,
            'acreditacion_acreditado_id' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeAcreditacionPending::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_via' => EmployeeAcreditacionPending::RESOLVED_VIA_FIRST_ACREDITADO,
            'omitted_at' => null,
            'omitted_by' => null,
            'omit_reason' => null,
        ]);
    }
}
