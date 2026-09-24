<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acreditacion_reporte_diario_filas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('carga_id')
                ->constrained('acreditacion_reporte_diario_cargas')
                ->cascadeOnDelete();
            $table->string('origen', 20);
            $table->string('apellido1', 100)->nullable();
            $table->string('apellido2', 100)->nullable();
            $table->string('nombre1', 100)->nullable();
            $table->string('nombre2', 100)->nullable();
            $table->string('full_name', 255);
            $table->string('document_number', 50);
            $table->string('cargo', 255)->nullable();
            $table->string('estado_apo', 100)->nullable();
            $table->date('vigencia_acr')->nullable();
            $table->unsignedInteger('source_row')->nullable();
            $table->timestamps();

            $table->index(['carga_id', 'origen']);
            $table->index('document_number');
            $table->index('full_name');
            $table->index(['carga_id', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acreditacion_reporte_diario_filas');
    }
};
