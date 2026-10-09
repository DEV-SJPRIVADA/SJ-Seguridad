<?php

namespace Database\Factories;

use App\Models\MtSt04Registro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MtSt04Registro>
 */
class MtSt04RegistroFactory extends Factory
{
    protected $model = MtSt04Registro::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fechaExamen1 = fake()->optional(0.8)->dateTimeBetween('-18 months', 'now');
        $fechaExamen2 = fake()->optional(0.7)->dateTimeBetween('-18 months', 'now');

        return [
            'document_number' => fake()->unique()->numerify('##########'),
            'arma' => fake()->optional()->randomElement([MtSt04Registro::ARMA_SI, MtSt04Registro::ARMA_NO]),
            'fecha_examen_1' => $fechaExamen1?->format('Y-m-d'),
            'fecha_vencimiento_1' => $fechaExamen1
                ? (clone $fechaExamen1)->modify('+364 days')->format('Y-m-d')
                : null,
            'apto' => fake()->optional()->randomElement([MtSt04Registro::APTO_SI, MtSt04Registro::APTO_NO]),
            'observaciones_1' => fake()->optional()->sentence(),
            'estado_1' => null,
            'fecha_examen_2' => $fechaExamen2?->format('Y-m-d'),
            'fecha_vencimiento_2' => $fechaExamen2
                ? (clone $fechaExamen2)->modify('+364 days')->format('Y-m-d')
                : null,
            'observaciones_2' => fake()->optional()->sentence(),
            'estado_2' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
