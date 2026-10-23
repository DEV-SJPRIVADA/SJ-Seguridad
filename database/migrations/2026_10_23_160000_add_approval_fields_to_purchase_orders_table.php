<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aprobación opcional de una OC: Compras la pide a un director o administrador.
     * `aprobacion_estado`: null (sin pedir) | pendiente | aprobada | rechazada. Rechazada deja la OC Anulada.
     */
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite): void {
            $table->string('aprobacion_estado', 20)->nullable()->index();

            foreach (['aprobacion_aprobador_id', 'aprobacion_solicitada_por'] as $column) {
                if ($isSqlite) {
                    $table->unsignedBigInteger($column)->nullable();
                } else {
                    $table->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
                }
            }

            $table->text('aprobacion_nota')->nullable();
            $table->timestamp('aprobacion_solicitada_at')->nullable();
            $table->text('aprobacion_comentario')->nullable();
            $table->timestamp('aprobacion_resuelta_at')->nullable();
        });
    }

    public function down(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite): void {
            if (! $isSqlite) {
                $table->dropForeign(['aprobacion_aprobador_id']);
                $table->dropForeign(['aprobacion_solicitada_por']);
            }

            $table->dropIndex(['aprobacion_estado']);
            $table->dropColumn([
                'aprobacion_estado',
                'aprobacion_aprobador_id',
                'aprobacion_solicitada_por',
                'aprobacion_nota',
                'aprobacion_solicitada_at',
                'aprobacion_comentario',
                'aprobacion_resuelta_at',
            ]);
        });
    }
};
