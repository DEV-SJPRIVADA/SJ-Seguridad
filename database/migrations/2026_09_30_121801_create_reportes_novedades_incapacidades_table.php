<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_novedades_incapacidades', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50)->index();
            $table->string('employee_name', 255);
            $table->string('cargo', 150)->nullable();
            $table->string('destino', 255)->nullable();
            $table->string('tipo_incapacidad', 40);
            $table->unsignedSmallInteger('dias');
            $table->date('fecha_inicio')->index();
            $table->date('fecha_fin')->index();
            $table->date('fecha_recepcion')->nullable();
            $table->date('fecha_devolucion')->nullable();
            $table->text('observacion_devolucion')->nullable();
            $table->date('fecha_registro_control_roll')->nullable();
            $table->date('fecha_envio_final')->nullable();
            $table->string('novedad_control_roll', 120)->nullable();
            $table->boolean('extemporanea')->default(false);
            $table->text('observaciones')->nullable();
            $table->string('observacion_nomina', 255)->nullable();
            $table->unsignedSmallInteger('dias_entrega')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_novedades_incapacidades');
    }
};
