<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_novedades_vacaciones', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50)->index();
            $table->string('employee_name', 255);
            $table->string('cargo', 150)->nullable();
            $table->string('destino', 255)->nullable();
            $table->string('novedad', 80);
            $table->unsignedSmallInteger('dias_novedad');
            $table->date('fecha_inicio')->index();
            $table->text('observaciones')->nullable();
            $table->string('observacion_nomina', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_novedades_vacaciones');
    }
};
