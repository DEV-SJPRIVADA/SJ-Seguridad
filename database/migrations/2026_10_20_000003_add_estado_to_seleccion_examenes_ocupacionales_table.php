<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado operativo del examen ocupacional (lista fija, no catálogo).
 * Registros existentes quedan en «solicitado» vía default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seleccion_examenes_ocupacionales', function (Blueprint $table): void {
            $table->string('estado', 30)->default('solicitado')->index()->after('fecha_arl');
        });
    }

    public function down(): void
    {
        Schema::table('seleccion_examenes_ocupacionales', function (Blueprint $table): void {
            $table->dropIndex(['estado']);
            $table->dropColumn('estado');
        });
    }
};
