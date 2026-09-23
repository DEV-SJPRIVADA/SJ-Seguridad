<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acreditacion_acreditados', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50);
            $table->string('full_name', 255);
            $table->string('cargo', 255);
            $table->string('cargo_apo', 255);
            $table->date('vigencia_acr')->nullable();
            $table->date('fecha_solicitud')->nullable();
            $table->string('estado', 30);
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_number', 'cargo_apo'], 'acreditacion_acreditados_cedula_apo_unique');
            $table->index('document_number');
            $table->index('estado');
            $table->index('vigencia_acr');
            $table->index('fecha_solicitud');
            $table->index('cargo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acreditacion_acreditados');
    }
};
