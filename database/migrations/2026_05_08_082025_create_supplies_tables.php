<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Catálogo Maestro de Productos
        Schema::create('supply_products', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('description')->nullable();
            $blueprint->string('category')->nullable();
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamps();
        });

        // 2. Cabecera de Solicitudes
        Schema::create('supply_requests', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->onDelete('cascade');
            $blueprint->string('area_key'); // Área solicitante
            $blueprint->string('status')->default('pendiente_calidad'); 
            // Estados: pendiente_calidad, aprobada_calidad, rechazada_calidad, en_compras, completada
            
            $blueprint->text('observations')->nullable();
            
            // Seguimiento de Calidad
            $blueprint->foreignId('quality_reviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $blueprint->text('quality_observations')->nullable();
            
            // Seguimiento de Compras
            $blueprint->foreignId('purchasing_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $blueprint->decimal('total_cost', 15, 2)->nullable();
            
            $blueprint->timestamps();
        });

        // 3. Detalle de Solicitudes
        Schema::create('supply_request_items', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('supply_request_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('supply_product_id')->constrained()->onDelete('cascade');
            
            $blueprint->integer('requested_quantity');
            $blueprint->integer('approved_quantity')->nullable();
            $blueprint->decimal('unit_cost', 15, 2)->nullable();
            
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_request_items');
        Schema::dropIfExists('supply_requests');
        Schema::dropIfExists('supply_products');
    }
};
