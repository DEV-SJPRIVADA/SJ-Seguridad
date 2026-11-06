<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_novedades_incapacidad_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incapacidad_id')
                ->constrained('reportes_novedades_incapacidades')
                ->cascadeOnDelete();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 127)->nullable();
            $table->unsignedInteger('size_bytes');
            $table->timestamps();

            $table->index(['incapacidad_id', 'id'], 'rn_incap_adjuntos_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_novedades_incapacidad_adjuntos');
    }
};
