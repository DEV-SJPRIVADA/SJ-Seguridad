<?php

namespace Database\Factories;

use App\Models\AcreditacionCargo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcreditacionCargo>
 */
class AcreditacionCargoFactory extends Factory
{
    protected $model = AcreditacionCargo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cargo_manager' => fake()->unique()->lexify('MANAGER-????'),
            'cargo_apo' => fake()->randomElement(['VIGILANTE', 'ESCOLTA', 'SUPERVISOR', 'OPERADOR DE MEDIOS TECNOLOGICOS']),
            'cargo_informe' => fake()->lexify('INFORME-????'),
            'cargo_acreditacion' => (string) fake()->randomElement(['1', '2', '4', '5', '6']),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
