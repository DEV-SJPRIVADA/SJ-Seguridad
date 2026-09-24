<?php

namespace Database\Factories;

use App\Models\AcreditacionReporteDiarioCarga;
use App\Models\AcreditacionReporteDiarioFila;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcreditacionReporteDiarioFila>
 */
class AcreditacionReporteDiarioFilaFactory extends Factory
{
    protected $model = AcreditacionReporteDiarioFila::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $apellido1 = fake()->lastName();
        $apellido2 = fake()->lastName();
        $nombre1 = fake()->firstName();
        $nombre2 = fake()->optional()->firstName();
        $parts = array_filter([$apellido1, $apellido2, $nombre1, $nombre2]);

        return [
            'carga_id' => AcreditacionReporteDiarioCarga::factory(),
            'origen' => AcreditacionReporteDiarioFila::ORIGEN_PROCESO,
            'apellido1' => $apellido1,
            'apellido2' => $apellido2,
            'nombre1' => $nombre1,
            'nombre2' => $nombre2,
            'full_name' => implode(' ', $parts),
            'document_number' => fake()->numerify('##########'),
            'cargo' => fake()->randomElement(['GUARDA', 'ESCOLTA', 'SUPERVISOR']),
            'estado_apo' => 'EN PROCESO',
            'vigencia_acr' => null,
            'source_row' => fake()->numberBetween(3, 100),
        ];
    }

    public function proceso(): static
    {
        return $this->state(fn (): array => [
            'origen' => AcreditacionReporteDiarioFila::ORIGEN_PROCESO,
            'estado_apo' => 'EN PROCESO',
            'vigencia_acr' => null,
        ]);
    }

    public function acreditado(): static
    {
        return $this->state(fn (): array => [
            'origen' => AcreditacionReporteDiarioFila::ORIGEN_ACREDITADO,
            'estado_apo' => null,
            'vigencia_acr' => fake()->dateTimeBetween('+30 days', '+2 years')->format('Y-m-d'),
        ]);
    }
}
