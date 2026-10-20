<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seleccion_ingresos', function (Blueprint $table): void {
            $table->date('fecha_asistencia')->nullable()->index()->after('fecha_ingreso');
            $table->string('estado_code', 50)->nullable()->index()->after('fecha_asistencia');
            $table->string('estado_name', 150)->nullable()->after('estado_code');
            $table->string('jefe_ope_code', 50)->nullable()->after('jefe_ope');
            $table->text('observaciones')->nullable()->after('jefe_ope_code');
        });
    }

    public function down(): void
    {
        Schema::table('seleccion_ingresos', function (Blueprint $table): void {
            $table->dropIndex(['fecha_asistencia']);
            $table->dropIndex(['estado_code']);
            $table->dropColumn([
                'fecha_asistencia',
                'estado_code',
                'estado_name',
                'jefe_ope_code',
                'observaciones',
            ]);
        });
    }
};
