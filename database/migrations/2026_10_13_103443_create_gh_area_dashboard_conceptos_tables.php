<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo editable: concepto/área ↔ cargos (Dashboard área GH).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gh_area_dashboard_conceptos', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('gh_area_dashboard_concepto_cargos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('concepto_id')
                ->constrained('gh_area_dashboard_conceptos')
                ->cascadeOnDelete();
            // Clave normalizada UPPER(TRIM) — un cargo solo en un concepto.
            $table->string('position_key', 255);
            $table->string('position_label', 255);
            $table->timestamps();

            $table->unique('position_key');
            $table->index('concepto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gh_area_dashboard_concepto_cargos');
        Schema::dropIfExists('gh_area_dashboard_conceptos');
    }
};
