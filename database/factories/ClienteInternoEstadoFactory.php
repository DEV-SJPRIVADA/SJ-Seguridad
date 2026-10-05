<?php

namespace Database\Factories;

use App\Models\ClienteInternoEstado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClienteInternoEstado>
 */
class ClienteInternoEstadoFactory extends Factory
{
    protected $model = ClienteInternoEstado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'code' => mb_strtoupper(str_replace(' ', '_', $name)),
            'name' => ucfirst($name),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
