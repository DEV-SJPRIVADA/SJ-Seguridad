<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Misión y visión del DOFA (Planeación estratégica).
 * Se agregan como texto libre nullable: los DOFA existentes quedan sin diligenciar.
 * Compatible MySQL 8 y sqlite (:memory: en tests).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_planning_dofas', function (Blueprint $table): void {
            $table->text('mission')->nullable()->after('summary');
            $table->text('vision')->nullable()->after('mission');
        });
    }

    public function down(): void
    {
        Schema::table('quality_planning_dofas', function (Blueprint $table): void {
            $table->dropColumn(['mission', 'vision']);
        });
    }
};
