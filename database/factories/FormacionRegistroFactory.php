<?php

namespace Database\Factories;

use App\Models\FormacionRegistro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormacionRegistro>
 */
class FormacionRegistroFactory extends Factory
{
    protected $model = FormacionRegistro::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fecha = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'numero_id' => fake()->numerify('##########'),
            'nombre_completo' => fake()->name(),
            'fecha_inicio' => $fecha->format('Y-m-d'),
            'mes' => (int) $fecha->format('n'),
            'anio' => (int) $fecha->format('Y'),
            'nombre_curso' => fake()->randomElement([
                'Inducción SST',
                'Trabajo en alturas',
                'Primeros auxilios',
                'Manejo defensivo',
            ]),
            'calificacion' => fake()->optional()->numerify('##'),
            'categoria' => fake()->randomElement([
                'Obligatoria',
                'Complementaria',
                'Recertificación',
            ]),
        ];
    }
}
