<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes MT-GI-11 y sus adjuntos.
 *
 * `consecutivo` es texto editable y NO unico: la llave operativa es `id`
 * (regla acordada con el usuario, porque un mismo consecutivo puede tener
 * varias versiones del documento).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calidad_mt_gi_11_solicitudes', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha_solicitud')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('proceso_id')->constrained('calidad_mt_gi_11_procesos')->restrictOnDelete();
            $table->string('consecutivo', 60)->index();
            $table->foreignId('tipo_documento_id')->constrained('calidad_mt_gi_11_tipos_documento')->restrictOnDelete();
            $table->string('nombre_documento', 255);
            $table->foreignId('tipo_solicitud_id')->constrained('calidad_mt_gi_11_tipos_solicitud')->restrictOnDelete();
            $table->string('codigo_actual', 60)->nullable();
            $table->string('version_actual', 30)->nullable();
            $table->text('justificacion');
            $table->string('responsable_proceso', 255);
            $table->foreignId('cargo_responsable_id')->constrained('calidad_mt_gi_11_cargos_responsable')->restrictOnDelete();
            $table->string('codigo_estandarizacion', 60)->nullable();
            $table->date('fecha_estandarizacion')->nullable();
            $table->unsignedInteger('version_actualizada')->nullable();
            $table->foreignId('estado_id')->constrained('calidad_mt_gi_11_estados')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('calidad_mt_gi_11_adjuntos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('calidad_mt_gi_11_solicitudes')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calidad_mt_gi_11_adjuntos');
        Schema::dropIfExists('calidad_mt_gi_11_solicitudes');
    }
};
