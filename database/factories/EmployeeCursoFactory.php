<?php

namespace Database\Factories;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeCurso>
 */
class EmployeeCursoFactory extends Factory
{
    protected $model = EmployeeCurso::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_number' => fake()->numerify('##########'),
            'full_name' => fake()->name(),
            'curso_tipo_id' => CursoTipo::factory(),
            'fecha_expedicion' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'numero_curso' => fake()->unique()->bothify('NC-####'),
            'estado' => null,
            'observaciones' => null,
            'document_path' => null,
            'document_original_name' => null,
            'document_mime' => null,
            'document_size_bytes' => null,
            'employee_ficha_profile_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
