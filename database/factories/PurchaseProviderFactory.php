<?php

namespace Database\Factories;

use App\Models\PurchaseProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseProvider>
 */
class PurchaseProviderFactory extends Factory
{
    protected $model = PurchaseProvider::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identificacion' => $this->faker->unique()->numerify('##########'),
            'nombre' => mb_strtoupper($this->faker->company()),
            'telefono' => $this->faker->numerify('3#########'),
            'correo' => $this->faker->unique()->safeEmail(),
            'es_cliente' => false,
            'es_proveedor' => true,
            'estado' => PurchaseProvider::ESTADO_ACTIVO,
            'dias_pago' => $this->faker->randomElement([15, 30, 45, 60]),
            'contacto' => mb_strtoupper($this->faker->name()),
            'direccion' => mb_strtoupper($this->faker->streetAddress()),
            'ciudad' => 'BOGOTÁ',
            'forma_pago' => 'TRANSFERENCIA',
            'created_by' => null,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => [
            'estado' => PurchaseProvider::ESTADO_INACTIVO,
        ]);
    }

    public function cliente(): static
    {
        return $this->state(fn (): array => [
            'es_cliente' => true,
            'es_proveedor' => false,
        ]);
    }
}
