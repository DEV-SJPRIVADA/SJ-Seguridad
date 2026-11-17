<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paso 1 de autorización de OC: quien ya autorizó la solicitud (director SC o Calidad SU).
     * El paso 2 sigue en `aprobacion_aprobador_id` (Director Admon. elegido por Compras).
     */
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite): void {
            if ($isSqlite) {
                $table->unsignedBigInteger('aprobacion_area_aprobador_id')->nullable();
            } else {
                $table->foreignId('aprobacion_area_aprobador_id')
                    ->nullable()
                    ->after('aprobacion_aprobador_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            $table->string('aprobacion_area_estado', 20)->nullable();
            $table->text('aprobacion_area_comentario')->nullable();
            $table->timestamp('aprobacion_area_resuelta_at')->nullable();
        });
    }

    public function down(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite): void {
            if (! $isSqlite) {
                $table->dropForeign(['aprobacion_area_aprobador_id']);
            }

            $table->dropColumn([
                'aprobacion_area_aprobador_id',
                'aprobacion_area_estado',
                'aprobacion_area_comentario',
                'aprobacion_area_resuelta_at',
            ]);
        });
    }
};
