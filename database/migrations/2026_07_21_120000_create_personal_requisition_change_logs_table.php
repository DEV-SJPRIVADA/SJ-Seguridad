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
        if (Schema::hasTable('personal_requisition_change_logs')) {
            return;
        }

        Schema::create('personal_requisition_change_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('personal_requisition_id')->constrained('personal_requisitions')->cascadeOnDelete();
            $table->uuid('change_batch');
            $table->string('field_key');
            $table->string('field_label');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['personal_requisition_id', 'created_at'], 'req_chg_logs_req_created_idx');
            $table->index('change_batch', 'req_chg_logs_batch_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_requisition_change_logs');
    }
};
