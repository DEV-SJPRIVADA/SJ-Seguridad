<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('identificacion', 50)->unique();
            $table->string('nombre', 255);
            $table->string('telefono', 50)->nullable();
            $table->string('correo', 255)->nullable();
            // activo | inactivo (string multi-driver; sin enum() MySQL)
            $table->string('estado', 20)->default('activo')->index();
            $table->unsignedSmallInteger('dias_pago')->nullable();
            $table->string('contacto', 255)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->string('ciudad', 120)->nullable();
            $table->string('forma_pago', 120)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_providers');
    }
};
