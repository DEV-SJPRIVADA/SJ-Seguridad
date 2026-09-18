<?php

namespace Database\Factories;

use App\Models\DevelopmentRequest;
use App\Models\DevelopmentRequestMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevelopmentRequestMessage>
 */
class DevelopmentRequestMessageFactory extends Factory
{
    protected $model = DevelopmentRequestMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'development_request_id' => DevelopmentRequest::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'attachment_path' => null,
        ];
    }
}
