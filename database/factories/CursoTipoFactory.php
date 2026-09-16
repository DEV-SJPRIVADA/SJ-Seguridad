<?php

namespace Database\Factories;

use App\Models\CursoTipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CursoTipo>
 */
class CursoTipoFactory extends Factory
{
    protected $model = CursoTipo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_curso' => fake()->unique()->lexify('CURSO-????'),
            'cargo_curso' => fake()->optional()->jobTitle(),
            'formato_para_cursos' => fake()->optional()->randomElement(['PRESENCIAL', 'VIRTUAL', 'MIXTO']),
            'cursos' => fake()->optional()->sentence(3),
            'cargo_acredit' => fake()->optional()->jobTitle(),
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
