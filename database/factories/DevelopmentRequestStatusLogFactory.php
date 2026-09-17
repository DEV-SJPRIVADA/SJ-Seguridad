<?php

namespace Database\Factories;

use App\Models\DevelopmentRequest;
use App\Models\DevelopmentRequestStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevelopmentRequestStatusLog>
 */
class DevelopmentRequestStatusLogFactory extends Factory
{
    protected $model = DevelopmentRequestStatusLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'development_request_id' => DevelopmentRequest::factory(),
            'user_id' => User::factory(),
            'from_status' => DevelopmentRequest::STATUS_BORRADOR,
            'to_status' => DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER,
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
