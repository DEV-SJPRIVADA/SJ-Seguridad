<?php

namespace Database\Factories\QualityPlanning;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityPlanningIndicator>
 */
class QualityPlanningIndicatorFactory extends Factory
{
    protected $model = QualityPlanningIndicator::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'IND-'.fake()->unique()->numerify('####'),
            'created_by_user_id' => User::factory(),
            'archived_at' => null,
            'current_published_version_id' => null,
        ];
    }
}
