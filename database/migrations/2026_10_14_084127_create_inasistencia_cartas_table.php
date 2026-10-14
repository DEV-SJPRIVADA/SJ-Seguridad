<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inasistencia_cartas', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50);
            $table->string('full_name', 255)->nullable();
            $table->string('cargo', 255)->nullable();
            $table->string('ciudad', 255)->nullable();
            $table->date('fecha_ini_aus');
            $table->unsignedInteger('dias');
            $table->string('firma_name', 255)->nullable();
            $table->string('cargo_firma', 255)->nullable();
            $table->foreignId('signatory_id')->nullable()->constrained('payroll_catalog_items')->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->foreignId('word_template_id')->nullable()->constrained('termination_letter_document_templates')->nullOnDelete();
            $table->timestamps();

            $table->index('document_number');
            $table->index('generated_at');
            $table->index('fecha_ini_aus');
            $table->index('signatory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inasistencia_cartas');
    }
};
