<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comodato y Activo quedan null en filas viejas y en solicitudes internas.
     * Sin default: un false de BD haría imprimir «No» al reimprimir.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->boolean('comodato')->nullable();
            $table->boolean('activo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->dropColumn(['comodato', 'activo']);
        });
    }
};
