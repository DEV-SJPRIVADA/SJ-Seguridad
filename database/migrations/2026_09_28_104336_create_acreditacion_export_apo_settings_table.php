<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acreditacion_export_apo_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('nit', 20);
            $table->string('razon_social', 255);
            $table->string('tipo_documento', 10);
            $table->string('tipo_establecimiento', 50);
            $table->string('telefono_r', 30);
            $table->string('direccion_r', 255);
            $table->string('direccion_p', 255);
            $table->string('departamento', 100);
            $table->string('ciudad', 100);
            $table->string('educacion_bm', 50);
            $table->string('educacion_s', 50);
            $table->string('discapacidad', 50);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acreditacion_export_apo_settings');
    }
};
