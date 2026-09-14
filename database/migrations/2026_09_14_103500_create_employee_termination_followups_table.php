<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_termination_followups', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('personal_requisition_ficha_entry_id');
            $table->unsignedBigInteger('employee_ficha_employment_period_id');
            $table->string('document_number', 50);
            $table->string('full_name', 255);
            $table->string('position_name', 150)->nullable();
            $table->string('termination_cause_code', 50)->nullable();
            $table->string('termination_cause_name', 150)->nullable();
            $table->boolean('is_rehireable')->nullable();
            $table->text('termination_notes')->nullable();
            $table->date('termination_date')->nullable();
            $table->timestamp('registered_at');
            $table->boolean('letter_generated')->default(false);
            $table->boolean('check_orden_examenes')->default(false);
            $table->boolean('check_enviado')->default(false);
            $table->boolean('check_control_roll')->default(false);
            $table->boolean('check_retiro_arl')->default(false);
            $table->boolean('check_retiro_cesantias')->default(false);
            $table->boolean('check_recibido')->default(false);
            $table->boolean('check_paz_y_salvo')->default(false);
            $table->boolean('check_reporte_noved')->default(false);
            $table->date('payroll_delivered_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('personal_requisition_ficha_entry_id', 'etf_ficha_entry_fk')
                ->references('id')
                ->on('personal_requisition_ficha_entries')
                ->cascadeOnDelete();
            $table->foreign('employee_ficha_employment_period_id', 'etf_period_fk')
                ->references('id')
                ->on('employee_ficha_employment_periods')
                ->cascadeOnDelete();
            $table->foreign('created_by', 'etf_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique('employee_ficha_employment_period_id', 'etf_period_unique');
            $table->index('document_number', 'etf_document_number_idx');
            $table->index('letter_generated', 'etf_letter_generated_idx');
            $table->index('registered_at', 'etf_registered_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_termination_followups');
    }
};
