<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot SI/NO al emitir. Null si el origen no traía el dato (interna, suministro u OC previa).
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->string('proyecto_nuevo', 20)->nullable();
            $table->string('reinversion', 20)->nullable();
            $table->string('comodato', 20)->nullable();
            $table->string('activo', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropColumn(['proyecto_nuevo', 'reinversion', 'comodato', 'activo']);
        });
    }
};
