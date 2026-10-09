<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mt_st_04_registros', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50);
            $table->string('arma', 2)->nullable();
            $table->date('fecha_examen_1')->nullable();
            $table->date('fecha_vencimiento_1')->nullable();
            $table->string('apto', 2)->nullable();
            $table->text('observaciones_1')->nullable();
            $table->string('estado_1', 20)->nullable();
            $table->date('fecha_examen_2')->nullable();
            $table->date('fecha_vencimiento_2')->nullable();
            $table->text('observaciones_2')->nullable();
            $table->string('estado_2', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('document_number');
            $table->index('estado_1');
            $table->index('estado_2');
            $table->index('fecha_vencimiento_1');
            $table->index('fecha_vencimiento_2');
            $table->index('apto');
            $table->index('arma');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mt_st_04_registros');
    }
};
