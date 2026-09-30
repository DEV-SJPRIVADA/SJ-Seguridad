<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_novedades_retiros', function (Blueprint $table): void {
            $table->id();
            $table->string('document_number', 50)->index();
            $table->string('employee_name', 255);
            $table->date('fecha_ingreso')->nullable();
            $table->string('tipo', 80)->nullable();
            $table->string('cargo', 150)->nullable();
            $table->string('destino', 255)->nullable();
            $table->string('novedad', 80)->default('RETIRO');
            $table->date('fecha_retiro')->nullable()->index();
            $table->string('motivo_retiro', 80)->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('employee_termination_followup_id')->nullable();
            $table->unsignedBigInteger('personal_requisition_ficha_entry_id')->nullable();
            $table->string('observacion_nomina', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('employee_termination_followup_id', 'rn_retiros_followup_unique');
            $table->foreign('employee_termination_followup_id', 'rn_retiros_followup_fk')
                ->references('id')
                ->on('employee_termination_followups')
                ->nullOnDelete();
            $table->foreign('personal_requisition_ficha_entry_id', 'rn_retiros_ficha_entry_fk')
                ->references('id')
                ->on('personal_requisition_ficha_entries')
                ->nullOnDelete();
            $table->foreign('created_by', 'rn_retiros_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('updated_by', 'rn_retiros_updated_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_novedades_retiros');
    }
};
