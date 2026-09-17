<?php

use App\Models\NotificationType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NotificationType::query()->firstOrCreate(
            [
                'module' => 'development_requests',
                'slug' => 'development_request_message',
            ],
            [
                'label' => 'Mensaje en solicitud de desarrollo',
                'description' => 'Correos TIC adicionales al hilo de conversacion de una solicitud.',
                'sort_order' => 10,
            ],
        );
    }

    public function down(): void
    {
        NotificationType::query()
            ->where('module', 'development_requests')
            ->where('slug', 'development_request_message')
            ->delete();
    }
};
