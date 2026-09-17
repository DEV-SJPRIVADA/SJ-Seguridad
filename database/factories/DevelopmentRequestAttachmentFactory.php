<?php

namespace Database\Factories;

use App\Models\DevelopmentRequest;
use App\Models\DevelopmentRequestAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DevelopmentRequestAttachment>
 */
class DevelopmentRequestAttachmentFactory extends Factory
{
    protected $model = DevelopmentRequestAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = Str::uuid()->toString();

        return [
            'development_request_id' => DevelopmentRequest::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => fake()->unique()->word().'.pdf',
            'stored_path' => 'development-requests/0/'.$uuid.'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 204800),
            'is_required_to_understand' => false,
            'sort_order' => 1,
        ];
    }
}
