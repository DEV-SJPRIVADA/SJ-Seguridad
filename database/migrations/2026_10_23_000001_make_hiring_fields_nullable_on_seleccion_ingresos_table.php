<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ingreso: cliente, dotación (tipo y tallas), RH y fecha de ingreso solo son obligatorios
 * cuando el estado es CONTRATADO; en otros estados pueden quedar vacíos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seleccion_ingresos', function (Blueprint $table): void {
            $table->unsignedBigInteger('commercial_client_id')->nullable()->change();
            $table->unsignedBigInteger('requisition_uniform_id')->nullable()->change();
            $table->string('shirt_size', 40)->nullable()->change();
            $table->string('pants_size', 40)->nullable()->change();
            $table->string('shoes_size', 40)->nullable()->change();
            $table->string('blood_type_code', 20)->nullable()->change();
            $table->string('blood_type_name', 40)->nullable()->change();
            $table->date('fecha_ingreso')->nullable()->change();
        });
    }

    /**
     * Revertir exige que no existan filas con estos campos vacíos.
     */
    public function down(): void
    {
        Schema::table('seleccion_ingresos', function (Blueprint $table): void {
            $table->unsignedBigInteger('commercial_client_id')->nullable(false)->change();
            $table->unsignedBigInteger('requisition_uniform_id')->nullable(false)->change();
            $table->string('shirt_size', 40)->nullable(false)->change();
            $table->string('pants_size', 40)->nullable(false)->change();
            $table->string('shoes_size', 40)->nullable(false)->change();
            $table->string('blood_type_code', 20)->nullable(false)->change();
            $table->string('blood_type_name', 40)->nullable(false)->change();
            $table->date('fecha_ingreso')->nullable(false)->change();
        });
    }
};
