<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acreditacion_reporte_diario_cargas', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha_reporte')->unique();
            $table->string('proceso_file_name', 255)->nullable();
            $table->unsignedInteger('proceso_rows_ok')->default(0);
            $table->unsignedInteger('proceso_rows_fail')->default(0);
            $table->timestamp('proceso_loaded_at')->nullable();
            $table->foreignId('proceso_loaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acreditado_file_name', 255)->nullable();
            $table->unsignedInteger('acreditado_rows_ok')->default(0);
            $table->unsignedInteger('acreditado_rows_fail')->default(0);
            $table->timestamp('acreditado_loaded_at')->nullable();
            $table->foreignId('acreditado_loaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('proceso_loaded_at');
            $table->index('acreditado_loaded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acreditacion_reporte_diario_cargas');
    }
};
