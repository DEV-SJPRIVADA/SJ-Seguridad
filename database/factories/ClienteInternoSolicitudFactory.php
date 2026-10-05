<?php

namespace Database\Factories;

use App\Models\ClienteInternoSolicitud;
use App\Models\ClienteInternoTipoSolicitud;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClienteInternoSolicitud>
 */
class ClienteInternoSolicitudFactory extends Factory
{
    protected $model = ClienteInternoSolicitud::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fecha = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'fecha_solicitud' => $fecha->format('Y-m-d'),
            'anio' => (int) $fecha->format('Y'),
            'mes' => (int) $fecha->format('n'),
            'nombre_apellidos' => fake()->name(),
            'cedula' => fake()->numerify('##########'),
            'correo_electronico' => fake()->optional()->safeEmail(),
            'tipo_solicitud_id' => ClienteInternoTipoSolicitud::factory(),
            'fecha_respuesta' => null,
            'estado_id' => null,
            'novedad' => fake()->optional()->sentence(),
            'dias_respuesta' => null,
            'dias_respuesta_manual' => false,
        ];
    }
}
