<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formacion_registros', function (Blueprint $table): void {
            $table->id();
            $table->string('numero_id', 50);
            $table->string('nombre_completo', 255);
            $table->date('fecha_inicio');
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->string('nombre_curso', 255);
            $table->string('calificacion', 50)->nullable();
            $table->string('categoria', 255);
            $table->timestamps();

            $table->index('numero_id');
            $table->index('anio');
            $table->index('mes');
            $table->index('categoria');
            $table->index('nombre_curso');
            $table->index(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formacion_registros');
    }
};
