<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Resultado de la última solicitud de anulación de la OC (historial de Autorizaciones compras).
     * `anulacion_resultado`: null (sin resolver) | aprobada | rechazada.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->string('anulacion_resultado', 20)->nullable()->after('anulacion_resuelta_at');
        });

        // Anulaciones ya resueltas: aprobada si la OC quedó anulada, rechazada si siguió vigente.
        DB::table('purchase_orders')
            ->whereNotNull('anulacion_resuelta_at')
            ->update([
                'anulacion_resultado' => DB::raw("CASE WHEN estado = 'anulada' THEN 'aprobada' ELSE 'rechazada' END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropColumn('anulacion_resultado');
        });
    }
};
