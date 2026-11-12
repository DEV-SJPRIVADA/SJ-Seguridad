<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Autorización obligatoria de la OC por el director administrativo.
     *
     * `aprobacion_requerida` marca las OC que deben pasar por autorización antes de
     * imprimirse o evaluarse. Las OC anteriores al cambio quedan en `false` con el
     * backfill para no bloquear documentos históricos ni inventar firmas.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->boolean('aprobacion_requerida')->default(true)->after('estado');
        });

        // Backfill: lo ya emitido antes de la regla no exige firma retroactiva.
        DB::table('purchase_orders')->update(['aprobacion_requerida' => false]);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropColumn('aprobacion_requerida');
        });
    }
};
