<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('employee_archive_consultation_items');

        Schema::create('employee_archive_consultation_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employee_archive_consultation_id');
            $table->unsignedBigInteger('personal_requisition_ficha_entry_id')->nullable();
            $table->string('document_number', 50);
            $table->string('full_name', 200)->nullable();
            $table->string('archive_shelf', 100)->nullable();
            $table->string('archive_box', 100)->nullable();
            $table->text('concept');
            $table->string('delivered_to', 150)->nullable();
            $table->boolean('received')->default(false);
            $table->text('observation')->nullable();
            $table->unsignedTinyInteger('week_of_month');
            $table->unsignedTinyInteger('month_number');
            $table->string('month_label', 20);
            $table->timestamps();

            $table->foreign('employee_archive_consultation_id', 'ea_consult_items_consultation_fk')
                ->references('id')
                ->on('employee_archive_consultations')
                ->cascadeOnDelete();
            $table->foreign('personal_requisition_ficha_entry_id', 'ea_consult_items_ficha_entry_fk')
                ->references('id')
                ->on('personal_requisition_ficha_entries')
                ->nullOnDelete();

            $table->index('document_number');
            $table->index('created_at');
            $table->index(['month_number', 'week_of_month'], 'ea_consult_items_month_week_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_archive_consultation_items');
    }
};
