<?php

namespace Database\Factories\QualityPlanning;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityPlanningIndicatorVersion>
 */
class QualityPlanningIndicatorVersionFactory extends Factory
{
    protected $model = QualityPlanningIndicatorVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'indicator_id' => QualityPlanningIndicator::factory(),
            'version_number' => 1,
            'status' => QualityPlanningIndicatorVersion::STATUS_DRAFT,
            'name' => 'EFICACIA DEL PROCESO',
            'objective' => 'MEDIR EL CUMPLIMIENTO DEL INDICADOR.',
            'insumos' => null,
            'medicion' => null,
            'process_key' => 'gestion_gerencial',
            'unit' => QualityPlanningIndicatorVersion::UNIT_PERCENTAGE,
            'frequency' => QualityPlanningIndicatorVersion::FREQUENCY_MONTHLY,
            'target_value' => 90,
            'target_operator' => '>=',
            'critical_value' => 70,
            'formula' => '((campo_1 / campo_2) + campo_3) * 100',
            'fields' => [
                ['key' => 'campo_1', 'label' => 'NUMERADOR', 'decimals' => 2, 'required' => true, 'sort' => 1],
                ['key' => 'campo_2', 'label' => 'DENOMINADOR', 'decimals' => 2, 'required' => true, 'sort' => 2],
                ['key' => 'campo_3', 'label' => 'FACTOR', 'decimals' => 2, 'required' => true, 'sort' => 3],
            ],
            'published_at' => null,
            'published_by_user_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => QualityPlanningIndicatorVersion::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }
}
