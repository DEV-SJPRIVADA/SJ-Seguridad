<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de tasas de IVA para las OC (Compras → Catálogos → IVA) y porcentaje elegido por línea de OC.
     * Las líneas ya emitidas conservan su valor de IVA y quedan con porcentaje vacío.
     */
    public function up(): void
    {
        Schema::create('purchase_tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 60);
            $table->decimal('porcentaje', 5, 2)->unique();
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('purchase_tax_rates')->insert([
            ['nombre' => 'EXENTO / EXCLUIDO', 'porcentaje' => 0, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'IVA 5%', 'porcentaje' => 5, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'IVA 19%', 'porcentaje' => 19, 'activo' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::table('purchase_order_items', function (Blueprint $table): void {
            $table->decimal('iva_porcentaje', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table): void {
            $table->dropColumn('iva_porcentaje');
        });

        Schema::dropIfExists('purchase_tax_rates');
    }
};
