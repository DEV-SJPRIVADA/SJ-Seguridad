<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitud de anulación de una OC: Compras la pide y un director elegido la aprueba.
     * El estado vive en `estado` (emitida | anulacion_pendiente | anulada); aquí solo los datos de la solicitud.
     */
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite): void {
            foreach (['anulacion_aprobador_id', 'anulacion_solicitada_por'] as $column) {
                if ($isSqlite) {
                    $table->unsignedBigInteger($column)->nullable();
                } else {
                    $table->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
                }
            }

            $table->text('anulacion_motivo')->nullable();
            $table->timestamp('anulacion_solicitada_at')->nullable();
            $table->text('anulacion_respuesta')->nullable();
            $table->timestamp('anulacion_resuelta_at')->nullable();
        });
    }

    public function down(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite): void {
            if (! $isSqlite) {
                $table->dropForeign(['anulacion_aprobador_id']);
                $table->dropForeign(['anulacion_solicitada_por']);
            }

            $table->dropColumn([
                'anulacion_aprobador_id',
                'anulacion_solicitada_por',
                'anulacion_motivo',
                'anulacion_solicitada_at',
                'anulacion_respuesta',
                'anulacion_resuelta_at',
            ]);
        });
    }
};
