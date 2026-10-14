<?php

namespace Database\Factories;

use App\Models\InasistenciaCarta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InasistenciaCarta>
 */
class InasistenciaCartaFactory extends Factory
{
    protected $model = InasistenciaCarta::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fechaIni = fake()->dateTimeBetween('-30 days', 'now');
        $dias = (int) $fechaIni->diff(new \DateTimeImmutable('today'))->days;

        return [
            'document_number' => fake()->numerify('##########'),
            'full_name' => fake()->name(),
            'cargo' => fake()->jobTitle(),
            'ciudad' => fake()->city(),
            'fecha_ini_aus' => $fechaIni->format('Y-m-d'),
            'dias' => max(0, $dias),
            'firma_name' => fake()->name(),
            'cargo_firma' => 'Directora GH',
            'signatory_id' => null,
            'generated_by' => User::factory(),
            'generated_at' => now(),
            'word_template_id' => null,
        ];
    }
}
