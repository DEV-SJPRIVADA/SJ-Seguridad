<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('orden')->default(1);
            $table->decimal('cantidad', 10, 2);
            $table->string('articulo')->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('valor_unidad', 15, 2);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2);
            $table->string('source_item_type')->nullable();
            $table->unsignedBigInteger('source_item_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
