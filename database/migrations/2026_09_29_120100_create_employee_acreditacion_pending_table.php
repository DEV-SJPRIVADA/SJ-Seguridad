<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_acreditacion_pending', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50);
            $table->string('full_name', 255)->nullable();
            $table->unsignedBigInteger('employee_ficha_profile_id')->nullable();
            $table->unsignedBigInteger('personal_requisition_ficha_entry_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('enqueued_at');
            $table->foreignId('enqueued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('omitted_at')->nullable();
            $table->foreignId('omitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('omit_reason', 1000)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolved_via', 30)->nullable();
            $table->unsignedBigInteger('acreditacion_acreditado_id')->nullable();
            $table->timestamps();

            $table->foreign('employee_ficha_profile_id', 'eap_profile_fk')
                ->references('id')
                ->on('employee_ficha_profiles')
                ->nullOnDelete();
            $table->foreign('personal_requisition_ficha_entry_id', 'eap_ficha_entry_fk')
                ->references('id')
                ->on('personal_requisition_ficha_entries')
                ->nullOnDelete();
            $table->foreign('acreditacion_acreditado_id', 'eap_acreditado_fk')
                ->references('id')
                ->on('acreditacion_acreditados')
                ->nullOnDelete();

            $table->index('document_number');
            $table->index(['status', 'document_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_acreditacion_pending');
    }
};
