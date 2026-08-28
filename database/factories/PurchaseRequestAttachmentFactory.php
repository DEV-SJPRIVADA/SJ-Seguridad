<?php

namespace Database\Factories;

use App\Models\PurchaseRequestAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PurchaseRequestAttachment>
 */
class PurchaseRequestAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $uuid = Str::uuid()->toString();

        return [
            'original_name' => fake()->unique()->word().'.pdf',
            'stored_path' => 'purchase-requests/0/'.$uuid.'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 204800),
            'sort_order' => 1,
        ];
    }
}
