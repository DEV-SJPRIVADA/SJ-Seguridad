<?php

namespace Database\Factories;

use App\Models\AcreditacionReporteDiarioCarga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcreditacionReporteDiarioCarga>
 */
class AcreditacionReporteDiarioCargaFactory extends Factory
{
    protected $model = AcreditacionReporteDiarioCarga::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha_reporte' => fake()->unique()->date(),
            'proceso_file_name' => null,
            'proceso_rows_ok' => 0,
            'proceso_rows_fail' => 0,
            'proceso_loaded_at' => null,
            'proceso_loaded_by' => null,
            'acreditado_file_name' => null,
            'acreditado_rows_ok' => 0,
            'acreditado_rows_fail' => 0,
            'acreditado_loaded_at' => null,
            'acreditado_loaded_by' => null,
        ];
    }

    public function withProceso(?int $userId = null, int $ok = 1): static
    {
        return $this->state(fn (): array => [
            'proceso_file_name' => 'proceso.xlsx',
            'proceso_rows_ok' => $ok,
            'proceso_rows_fail' => 0,
            'proceso_loaded_at' => now(),
            'proceso_loaded_by' => $userId,
        ]);
    }

    public function withAcreditado(?int $userId = null, int $ok = 1): static
    {
        return $this->state(fn (): array => [
            'acreditado_file_name' => 'acreditado.xlsx',
            'acreditado_rows_ok' => $ok,
            'acreditado_rows_fail' => 0,
            'acreditado_loaded_at' => now(),
            'acreditado_loaded_by' => $userId,
        ]);
    }
}
