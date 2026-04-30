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
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            // Eliminar campos viejos
            $table->dropColumn(['contract_type', 'required_uniform', 'hired_quantity']);

            // Añadir nuevas relaciones
            $table->foreignId('contract_type_id')->nullable()->after('replacement_name')->constrained('requisition_contract_types');
            $table->foreignId('uniform_id')->nullable()->after('required_profile')->constrained('requisition_uniforms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->dropForeign(['contract_type_id']);
            $table->dropForeign(['uniform_id']);
            $table->dropColumn(['contract_type_id', 'uniform_id']);

            $table->string('contract_type')->nullable();
            $table->string('required_uniform')->nullable();
            $table->unsignedSmallInteger('hired_quantity')->default(0);
        });
    }
};
