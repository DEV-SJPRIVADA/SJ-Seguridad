<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reportes_novedades_vacaciones')) {
            return;
        }

        if (! Schema::hasColumn('reportes_novedades_vacaciones', 'fecha_fin')) {
            return;
        }

        Schema::table('reportes_novedades_vacaciones', function (Blueprint $table): void {
            $table->dropColumn('fecha_fin');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('reportes_novedades_vacaciones')) {
            return;
        }

        if (Schema::hasColumn('reportes_novedades_vacaciones', 'fecha_fin')) {
            return;
        }

        Schema::table('reportes_novedades_vacaciones', function (Blueprint $table): void {
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
        });
    }
};
