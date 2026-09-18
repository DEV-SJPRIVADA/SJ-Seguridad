<?php

namespace Database\Factories;

use App\Models\CursoEscuela;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CursoEscuela>
 */
class CursoEscuelaFactory extends Factory
{
    protected $model = CursoEscuela::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => (string) fake()->unique()->numberBetween(1, 9999),
            'nit' => (string) fake()->unique()->numerify('##########'),
            'nombre' => mb_strtoupper(fake()->company()),
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
