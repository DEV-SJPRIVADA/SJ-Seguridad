<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cotizaciones cargadas por Compras en la bandeja. No reemplazan los adjuntos de la
     * solicitud: aquí cada archivo tiene estado de aprobación y un director asignado.
     */
    public function up(): void
    {
        Schema::create('purchase_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 127)->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->string('description', 500)->nullable();
            // cargada | pendiente | aprobada | rechazada
            $table->string('estado', 20)->default('cargada')->index();
            $table->foreignId('aprobador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('solicitada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('solicitada_at')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamp('resuelta_at')->nullable();
            $table->timestamps();

            $table->index(['purchase_request_id', 'estado'], 'pq_request_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_quotations');
    }
};
