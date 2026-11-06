<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_cliente_caso_observaciones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('caso_id')->constrained('servicio_cliente_casos')->cascadeOnDelete();
            $table->text('texto');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('servicio_cliente_caso_adjuntos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('caso_id')->constrained('servicio_cliente_casos')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_cliente_caso_adjuntos');
        Schema::dropIfExists('servicio_cliente_caso_observaciones');
    }
};
