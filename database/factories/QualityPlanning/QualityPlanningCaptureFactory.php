<?php

namespace Database\Factories\QualityPlanning;

use App\Models\QualityPlanning\QualityPlanningCapture;
use App\Models\QualityPlanning\QualityPlanningIndicatorVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityPlanningCapture>
 */
class QualityPlanningCaptureFactory extends Factory
{
    protected $model = QualityPlanningCapture::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'indicator_version_id' => QualityPlanningIndicatorVersion::factory(),
            'user_id' => User::factory(),
            'year' => 2026,
            'month' => 4,
            'input_data' => [
                'campo_1' => 22,
                'campo_2' => 25,
                'campo_3' => 0.02,
            ],
            'result_value' => 90,
            'complies' => true,
            'analysis_text' => 'CUMPLIMIENTO DEL MES',
            'improvement_required' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }
}
