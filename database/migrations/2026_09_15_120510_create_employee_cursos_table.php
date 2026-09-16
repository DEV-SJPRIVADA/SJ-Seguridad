<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_cursos', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50);
            $table->string('full_name', 255);
            $table->foreignId('curso_tipo_id')->constrained('curso_tipos')->restrictOnDelete();
            $table->date('fecha_expedicion');
            $table->string('numero_curso', 100);
            $table->string('estado', 20)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->string('document_original_name', 255)->nullable();
            $table->string('document_mime', 127)->nullable();
            $table->unsignedInteger('document_size_bytes')->nullable();
            $table->foreignId('employee_ficha_profile_id')
                ->nullable()
                ->constrained('employee_ficha_profiles')
                ->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_number', 'numero_curso'], 'employee_cursos_cedula_numero_unique');
            $table->index('fecha_expedicion');
            $table->index('estado');
            $table->index('curso_tipo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_cursos');
    }
};
