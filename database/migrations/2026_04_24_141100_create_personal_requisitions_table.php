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
        Schema::create('personal_requisitions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('managed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('request_date');
            $table->string('leader_name');
            $table->string('requesting_area_key');
            $table->foreignId('position_id')->constrained('requisition_positions');
            $table->string('sex', 20)->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->string('replacement_document')->nullable();
            $table->string('replacement_name')->nullable();
            $table->string('operating_area_key')->nullable();
            $table->foreignId('request_reason_id')->constrained('requisition_request_reasons');
            $table->foreignId('client_id')->constrained('requisition_clients');
            $table->foreignId('city_id')->constrained('requisition_cities');
            $table->foreignId('client_type_id')->constrained('requisition_client_types');
            $table->foreignId('programming_type_id')->constrained('requisition_programming_types');
            $table->text('required_profile');
            $table->string('required_uniform')->nullable();
            $table->string('cost_center')->nullable();
            $table->text('requester_observation')->nullable();
            $table->text('human_resources_observation')->nullable();
            $table->string('status')->default('solicitada');
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['requesting_area_key', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_requisitions');
    }
};
