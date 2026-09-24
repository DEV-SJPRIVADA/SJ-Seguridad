<?php

namespace Database\Factories;

use App\Models\AcreditacionAcreditado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcreditacionAcreditado>
 */
class AcreditacionAcreditadoFactory extends Factory
{
    protected $model = AcreditacionAcreditado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_number' => fake()->numerify('##########'),
            'full_name' => fake()->name(),
            'cargo' => fake()->randomElement(['GUARDA', 'ESCOLTA', 'SUPERVISOR']),
            'cargo_apo' => fake()->randomElement(['VIGILANTE', 'ESCOLTA', 'SUPERVISOR']),
            'vigencia_acr' => fake()->dateTimeBetween('+30 days', '+2 years')->format('Y-m-d'),
            'fecha_solicitud' => null,
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'renovacion' => null,
            'observaciones' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function enProceso(): static
    {
        return $this->state(fn (): array => [
            'fecha_solicitud' => now()->toDateString(),
            'vigencia_acr' => null,
            'estado' => AcreditacionAcreditado::ESTADO_EN_PROCESO,
        ]);
    }

    public function desacreditado(): static
    {
        return $this->state(fn (): array => [
            'fecha_solicitud' => null,
            'vigencia_acr' => now()->subDay()->toDateString(),
            'estado' => AcreditacionAcreditado::ESTADO_DESACREDITADO,
        ]);
    }

    public function porVencer(): static
    {
        return $this->state(fn (): array => [
            'fecha_solicitud' => null,
            'vigencia_acr' => now()->addDays(10)->toDateString(),
            'estado' => AcreditacionAcreditado::ESTADO_POR_VENCER,
        ]);
    }
}
