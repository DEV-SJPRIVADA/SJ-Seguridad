<?php

namespace Database\Factories;

use App\Models\AcreditacionExportApoRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AcreditacionExportApoRun>
 */
class AcreditacionExportApoRunFactory extends Factory
{
    protected $model = AcreditacionExportApoRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exportDate = Carbon::now('America/Bogota')->toDateString();
        $seq = fake()->unique()->numberBetween(1, 999);

        return [
            'export_date' => $exportDate,
            'seq' => $seq,
            'file_name' => sprintf('APO9005767186%s%s.xls', Carbon::parse($exportDate)->format('Ymd'), str_pad((string) $seq, 3, '0', STR_PAD_LEFT)),
            'user_id' => User::factory(),
            'vigencia_policy' => 'VIGENTE',
            'include_novedades' => false,
            'rows_selected' => 1,
            'rows_ok' => 1,
            'rows_novedad' => 0,
            'rows_blocked' => 0,
            'rows_exported' => 1,
        ];
    }
}
