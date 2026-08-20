<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->dropColumn(['descripcion', 'justificacion']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->text('descripcion')->nullable()->after('fecha_solicitud');
            $table->text('justificacion')->nullable()->after('cantidad');
        });
    }
};
