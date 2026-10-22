<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos generales: número de cotización y comentarios del solicitante (ambos opcionales).
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->string('numero_cotizacion', 100)->nullable();
            $table->text('comentarios_solicitante')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->dropColumn(['numero_cotizacion', 'comentarios_solicitante']);
        });
    }
};
