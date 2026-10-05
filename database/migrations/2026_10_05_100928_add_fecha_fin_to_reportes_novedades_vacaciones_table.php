<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reportes_novedades_vacaciones')) {
            return;
        }

        if (! Schema::hasColumn('reportes_novedades_vacaciones', 'fecha_fin')) {
            Schema::table('reportes_novedades_vacaciones', function (Blueprint $table): void {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio')->index();
            });
        }

        // Backfill: fin = inicio + dias - 1 (mínimo = inicio si dias=0).
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement(
                'UPDATE reportes_novedades_vacaciones
                 SET fecha_fin = date(fecha_inicio, \'+\' || CASE WHEN dias_novedad > 0 THEN dias_novedad - 1 ELSE 0 END || \' days\')
                 WHERE fecha_fin IS NULL AND fecha_inicio IS NOT NULL'
            );
        } else {
            DB::statement(
                'UPDATE reportes_novedades_vacaciones
                 SET fecha_fin = DATE_ADD(fecha_inicio, INTERVAL GREATEST(CAST(dias_novedad AS SIGNED) - 1, 0) DAY)
                 WHERE fecha_fin IS NULL AND fecha_inicio IS NOT NULL'
            );
        }
    }

    public function down(): void
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
};
