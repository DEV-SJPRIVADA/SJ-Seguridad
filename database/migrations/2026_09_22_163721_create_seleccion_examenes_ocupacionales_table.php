<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seleccion_examenes_ocupacionales', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50)->index();
            $table->string('full_name', 255);
            $table->string('position_code', 50);
            $table->string('position_name', 150);
            $table->string('servicio_sector', 255);
            $table->foreignId('commercial_client_id')
                ->constrained('commercial_clients')
                ->restrictOnDelete();
            $table->string('eps_code', 50);
            $table->string('eps_name', 150);
            $table->string('afp_code', 50);
            $table->string('afp_name', 150);
            $table->date('birth_date');
            $table->string('city_code', 50);
            $table->string('city_name', 150);
            $table->string('address', 255);
            $table->string('email', 150);
            $table->string('phone', 40);
            $table->string('marital_status_code', 50);
            $table->string('marital_status_name', 150);
            $table->date('fecha_arl')->index();
            $table->string('solicitud_status_code', 50)->index();
            $table->string('solicitud_status_name', 150);
            $table->foreignId('responsable_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seleccion_examenes_ocupacionales');
    }
};
