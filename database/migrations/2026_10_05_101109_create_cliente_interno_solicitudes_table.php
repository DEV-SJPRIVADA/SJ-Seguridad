<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_interno_solicitudes', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha_solicitud')->index();
            $table->unsignedSmallInteger('anio')->index();
            $table->unsignedTinyInteger('mes')->index();
            $table->string('nombre_apellidos', 255);
            $table->string('cedula', 50)->index();
            $table->string('correo_electronico', 150)->nullable();
            $table->foreignId('tipo_solicitud_id')
                ->constrained('cliente_interno_tipos_solicitud')
                ->restrictOnDelete();
            $table->date('fecha_respuesta')->nullable();
            $table->foreignId('estado_id')
                ->nullable()
                ->constrained('cliente_interno_estados')
                ->nullOnDelete();
            $table->text('novedad')->nullable();
            $table->unsignedInteger('dias_respuesta')->nullable();
            $table->boolean('dias_respuesta_manual')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_interno_solicitudes');
    }
};
