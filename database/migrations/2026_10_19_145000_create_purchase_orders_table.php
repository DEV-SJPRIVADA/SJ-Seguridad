<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();                                    // = número OC visible (numeroOc())
            $table->string('source_type', 255);             // morphTo: PurchaseRequest | SupplyRequest
            $table->unsignedBigInteger('source_id');       // PK del origen
            $table->foreignId('provider_id')
                ->nullable()
                ->constrained('purchase_providers')
                ->nullOnDelete();                            // prefer restrict si hay OC
            // Snapshot de datos de proveedor al emitir
            $table->string('provider_identificacion', 50);
            $table->string('provider_nombre', 255);
            $table->string('provider_telefono', 50)->nullable();
            $table->string('provider_correo', 255)->nullable();
            $table->string('provider_contacto', 255)->nullable();
            $table->string('provider_direccion', 500)->nullable();
            $table->string('provider_ciudad', 120)->nullable();
            $table->string('provider_forma_pago', 120)->nullable();
            $table->unsignedSmallInteger('provider_dias_pago')->nullable();
            $table->string('fecha', 10);                    // fecha de emisión
            $table->string('ciudad', 120)->nullable();     // cabecera OC (ciudad de entrega)
            $table->string('asume_cliente', 20)->nullable(); // desde origen compra si aplica
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)
                ->default('borrador')                       // borrador | emitida
                ->index();                                  // multi-driver: string, no enum()
            $table->decimal('total', 15, 2)->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            // Índice compuesto para consultas rápidas por origen
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
