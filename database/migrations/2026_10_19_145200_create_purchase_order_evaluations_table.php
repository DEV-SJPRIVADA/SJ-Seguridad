<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')
                ->constrained()
                ->cascadeOnDelete();
            // 1:1 por OC
            $table->unique('purchase_order_id');

            $table->date('fecha_recibido')->nullable();
            // Calificaciones 1-5 por criterio (pesos 25/25/50)
            $table->unsignedTinyInteger('calif_referencias_cantidades')->nullable();
            $table->unsignedTinyInteger('calif_oportunidad_entrega')->nullable();
            $table->unsignedTinyInteger('calif_calidad_producto')->nullable();

            // Resultados calculados (cache)
            $table->decimal('resultado_referencias', 5, 2)->nullable();
            $table->decimal('resultado_oportunidad', 5, 2)->nullable();
            $table->decimal('resultado_calidad', 5, 2)->nullable();

            $table->decimal('puntaje_porcentaje', 5, 2)->nullable(); // 0-100
            $table->string('umbral_codigo', 20)
                ->nullable(); // satisfactoria / seguimiento / insatisfactoria
            $table->text('observaciones')->nullable();

            $table->foreignId('evaluated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_evaluations');
    }
};
