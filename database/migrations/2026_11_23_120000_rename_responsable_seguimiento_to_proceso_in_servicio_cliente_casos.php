<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FO-GI-22: la columna «RESP. SEGUIMIENTO» del listado pasa a llamarse «PROCESO».
 * Solo renombra; la columna de texto se elimina en la migración siguiente, cuando
 * «Proceso» pasa a ser catálogo (`2026_11_23_130000_...`).
 * Funciona en MySQL y en sqlite (comprobando columnas antes de renombrar).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('servicio_cliente_casos', 'responsable_seguimiento')) {
            return;
        }

        if (Schema::hasColumn('servicio_cliente_casos', 'proceso')) {
            return;
        }

        Schema::table('servicio_cliente_casos', function (Blueprint $table): void {
            $table->renameColumn('responsable_seguimiento', 'proceso');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('servicio_cliente_casos', 'proceso')) {
            return;
        }

        if (Schema::hasColumn('servicio_cliente_casos', 'responsable_seguimiento')) {
            return;
        }

        Schema::table('servicio_cliente_casos', function (Blueprint $table): void {
            $table->renameColumn('proceso', 'responsable_seguimiento');
        });
    }
};
