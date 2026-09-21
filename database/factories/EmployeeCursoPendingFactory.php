<?php

namespace Database\Factories;

use App\Models\EmployeeCursoPending;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeCursoPending>
 */
class EmployeeCursoPendingFactory extends Factory
{
    protected $model = EmployeeCursoPending::class;

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
            'status' => EmployeeCursoPending::STATUS_PENDING,
            'enqueued_at' => now(),
            'enqueued_by' => null,
            'omitted_at' => null,
            'omitted_by' => null,
            'omit_reason' => null,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolved_via' => null,
            'employee_curso_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeCursoPending::STATUS_PENDING,
            'omitted_at' => null,
            'omitted_by' => null,
            'omit_reason' => null,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolved_via' => null,
            'employee_curso_id' => null,
        ]);
    }

    public function omitted(?string $reason = null): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeCursoPending::STATUS_OMITTED,
            'omitted_at' => now(),
            'omit_reason' => $reason,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolved_via' => null,
            'employee_curso_id' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => EmployeeCursoPending::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_via' => EmployeeCursoPending::RESOLVED_VIA_FIRST_CURSO,
            'omitted_at' => null,
            'omitted_by' => null,
            'omit_reason' => null,
        ]);
    }
}
