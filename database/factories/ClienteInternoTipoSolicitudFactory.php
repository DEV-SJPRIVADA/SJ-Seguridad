<?php

namespace Database\Factories;

use App\Models\ClienteInternoTipoSolicitud;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClienteInternoTipoSolicitud>
 */
class ClienteInternoTipoSolicitudFactory extends Factory
{
    protected $model = ClienteInternoTipoSolicitud::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'code' => mb_strtoupper(str_replace(' ', '_', $name)),
            'name' => ucfirst($name),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
