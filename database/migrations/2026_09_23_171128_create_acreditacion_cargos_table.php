<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acreditacion_cargos', function (Blueprint $table): void {
            $table->id();
            $table->string('cargo_manager', 255);
            $table->string('cargo_apo', 255);
            $table->string('cargo_informe', 255);
            $table->string('cargo_acreditacion', 20);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['cargo_manager', 'cargo_apo'], 'acreditacion_cargos_manager_apo_unique');
            $table->index('cargo_apo');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acreditacion_cargos');
    }
};
