<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_planning_indicator_versions', function (Blueprint $table): void {
            $table->text('insumos')->nullable();
            $table->text('medicion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quality_planning_indicator_versions', function (Blueprint $table): void {
            $table->dropColumn(['insumos', 'medicion']);
        });
    }
};
