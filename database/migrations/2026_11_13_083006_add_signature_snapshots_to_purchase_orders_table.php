<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Copia de las cuatro firmas del FO-AD-07 al autorizar la OC.
     * Claves: elaborado, revisor, aprobado, gerente. Cada una: user_id + path.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->json('signature_snapshots')->nullable()->after('aprobacion_resuelta_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropColumn('signature_snapshots');
        });
    }
};
