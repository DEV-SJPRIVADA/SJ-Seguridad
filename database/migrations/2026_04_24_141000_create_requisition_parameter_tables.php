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
        foreach ([
            'requisition_positions',
            'requisition_request_reasons',
            'requisition_clients',
            'requisition_cities',
            'requisition_client_types',
            'requisition_programming_types',
        ] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['name'], $tableName.'_name_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_programming_types');
        Schema::dropIfExists('requisition_client_types');
        Schema::dropIfExists('requisition_cities');
        Schema::dropIfExists('requisition_clients');
        Schema::dropIfExists('requisition_request_reasons');
        Schema::dropIfExists('requisition_positions');
    }
};
