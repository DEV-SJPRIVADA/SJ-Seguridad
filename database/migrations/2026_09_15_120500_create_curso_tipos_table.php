<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_tipos', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_curso', 150)->unique();
            $table->string('cargo_curso', 150)->nullable();
            $table->string('formato_para_cursos', 150)->nullable();
            $table->string('cursos', 255)->nullable();
            $table->string('cargo_acredit', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_tipos');
    }
};
