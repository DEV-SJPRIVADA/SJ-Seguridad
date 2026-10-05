<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_interno_estados', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed ESTADO: valores fijos del catálogo (upsert por code).
        $now = now();
        foreach ([
            ['code' => 'PENDIENTE', 'name' => 'Pendiente', 'sort_order' => 1],
            ['code' => 'EN_PROCESO', 'name' => 'En proceso', 'sort_order' => 2],
            ['code' => 'RESPONDIDA', 'name' => 'Respondida', 'sort_order' => 3],
            ['code' => 'CERRADA', 'name' => 'Cerrada', 'sort_order' => 4],
        ] as $row) {
            DB::table('cliente_interno_estados')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_interno_estados');
    }
};
