<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('numero_solicitud')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('area_key');
            $table->date('fecha_solicitud');
            $table->text('descripcion');
            $table->unsignedInteger('cantidad')->default(1);
            $table->text('justificacion')->nullable();
            $table->string('archivo_pedido_path')->nullable();
            $table->enum('solicitud_para', ['Interno', 'Cliente'])->default('Interno');
            $table->boolean('urgente')->default(false);
            $table->foreignId('aprobador_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('proyecto_nuevo')->nullable();
            $table->string('razon_social')->nullable();
            $table->boolean('asume_cliente')->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->enum('estado_compras', ['pendiente', 'en_curso', 'completado', 'rechazado'])->nullable();
            $table->date('fecha_aprobacion')->nullable();
            $table->text('comentarios_director')->nullable();
            $table->timestamp('procesado_compras_at')->nullable();
            $table->foreignId('procesado_compras_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comentarios_compras')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->unsignedInteger('cantidad');
            $table->string('foto_path')->nullable();
            $table->text('descripcion');
            $table->string('referencia');
            $table->text('utilizacion');
            $table->string('ubicacion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
    }
};
