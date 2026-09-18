<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('area_key')->index();
            $table->string('status')->default('borrador')->index();
            $table->string('request_type')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('associated_norm')->nullable();
            $table->string('proceso_sede')->nullable();

            $table->string('requester_name')->nullable();
            $table->string('requester_position')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_phone')->nullable();

            $table->text('description')->nullable();
            $table->text('current_process_problem')->nullable();
            $table->text('desired_steps')->nullable();
            $table->text('users_description')->nullable();
            $table->text('restrictions')->nullable();
            $table->text('scope_in')->nullable();
            $table->text('scope_out')->nullable();
            $table->text('acceptance_criteria')->nullable();
            $table->json('permissions_matrix')->nullable();
            $table->text('reports')->nullable();

            $table->string('suggested_priority')->nullable()->index();
            $table->date('desired_date')->nullable();
            $table->text('desired_date_justification')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_programmer_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('leader_decided_at')->nullable();
            $table->text('leader_decision_notes')->nullable();
            $table->timestamp('radicated_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Bloque TIC (FO-TIC-23 interno)
            $table->string('tic_viability')->nullable();
            $table->string('tic_confirmed_priority')->nullable();
            $table->string('tic_complexity')->nullable();
            $table->boolean('requires_fo_ge_12')->default(false);
            $table->boolean('requires_extended_analysis')->default(false);
            $table->date('tic_estimated_date')->nullable();
            $table->text('tic_risks')->nullable();
            $table->text('tic_analysis_notes')->nullable();
            $table->string('uat_result')->nullable();
            $table->text('uat_notes')->nullable();
            $table->text('closure_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['area_key', 'status']);
            $table->index(['created_by', 'status']);
            $table->index(['leader_id', 'status']);
            $table->index(['assigned_programmer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_requests');
    }
};
