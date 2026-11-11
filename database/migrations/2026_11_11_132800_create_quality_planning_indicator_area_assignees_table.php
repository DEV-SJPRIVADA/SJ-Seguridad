<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_planning_indicator_area_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_id')->constrained('quality_planning_indicators')->cascadeOnDelete();
            $table->string('area_key', 80);
            $table->boolean('can_capture')->default(true);
            $table->boolean('can_consult')->default(false);
            $table->timestamps();

            $table->unique(['indicator_id', 'area_key'], 'qp_indicator_area_assignees_unique');
            $table->index('area_key', 'qp_indicator_area_assignees_area_key_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_planning_indicator_area_assignees');
    }
};
