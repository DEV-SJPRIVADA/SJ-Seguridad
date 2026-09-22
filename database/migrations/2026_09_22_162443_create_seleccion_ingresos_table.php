<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seleccion_ingresos', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50)->index();
            $table->string('full_name', 255);
            $table->string('email', 150);
            $table->string('phone', 40);
            $table->string('city_code', 50);
            $table->string('city_name', 150);
            $table->string('position_code', 50);
            $table->string('position_name', 150);
            $table->foreignId('commercial_client_id')
                ->constrained('commercial_clients')
                ->restrictOnDelete();
            $table->string('shirt_size', 40);
            $table->string('pants_size', 40);
            $table->string('shoes_size', 40);
            $table->foreignId('requisition_uniform_id')
                ->constrained('requisition_uniforms')
                ->restrictOnDelete();
            $table->date('fecha_ingreso')->index();
            $table->string('blood_type_code', 20);
            $table->string('blood_type_name', 40);
            $table->string('reemplaza_a', 255);
            $table->foreignId('responsable_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('jefe_ope', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seleccion_ingresos');
    }
};
