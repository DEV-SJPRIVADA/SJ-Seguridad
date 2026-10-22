<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca del producto (pedido de Comercial), opcional y al lado de la referencia.
     */
    public function up(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table): void {
            $table->string('marca', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table): void {
            $table->dropColumn('marca');
        });
    }
};
