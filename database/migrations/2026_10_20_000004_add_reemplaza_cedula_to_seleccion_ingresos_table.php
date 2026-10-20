<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cédula de la persona a la que reemplaza el aspirante (opcional; autollenado desde Ficha).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seleccion_ingresos', function (Blueprint $table): void {
            $table->string('reemplaza_cedula', 50)->nullable()->after('blood_type_name');
        });
    }

    public function down(): void
    {
        Schema::table('seleccion_ingresos', function (Blueprint $table): void {
            $table->dropColumn('reemplaza_cedula');
        });
    }
};
