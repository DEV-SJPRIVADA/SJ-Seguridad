<?php

namespace Database\Factories;

use App\Models\DevelopmentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevelopmentRequest>
 */
class DevelopmentRequestFactory extends Factory
{
    protected $model = DevelopmentRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => null,
            'area_key' => 'tic',
            'status' => DevelopmentRequest::STATUS_BORRADOR,
            'request_type' => DevelopmentRequest::TYPE_MEJORA,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'suggested_priority' => DevelopmentRequest::PRIORITY_IMPORTANTE,
            'created_by' => User::factory(),
            'leader_id' => null,
            'assigned_programmer_id' => null,
        ];
    }

    public function radicado(): static
    {
        return $this->state(fn (): array => [
            'status' => DevelopmentRequest::STATUS_RADICADO,
            'code' => 'DEV-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'radicated_at' => now(),
            'submitted_at' => now()->subHour(),
        ]);
    }
}
