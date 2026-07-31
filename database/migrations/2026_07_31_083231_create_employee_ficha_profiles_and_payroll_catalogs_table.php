<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->string('catalog_type', 50);
            $table->string('code', 50);
            $table->string('name', 255);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['catalog_type', 'code'], 'payroll_catalog_type_code_uq');
            $table->index(['catalog_type', 'is_active']);
        });

        Schema::create('requisition_position_payroll_maps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('requisition_position_id');
            $table->string('payroll_position_code', 50);
            $table->timestamps();

            $table->unique('requisition_position_id', 'req_pos_payroll_map_pos_uq');
            $table->foreign('requisition_position_id', 'req_pos_payroll_map_pos_fk')
                ->references('id')
                ->on('requisition_positions')
                ->cascadeOnDelete();
        });

        Schema::create('employee_ficha_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('personal_requisition_ficha_entry_id')->nullable();
            $table->string('document_number', 50);
            $table->string('full_name', 255)->nullable();
            $table->string('first_surname', 100)->nullable();
            $table->string('second_surname', 100)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('second_name', 100)->nullable();
            $table->string('document_type', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('expedition_city_code', 20)->nullable();
            $table->string('expedition_city_name', 100)->nullable();
            $table->date('expedition_date')->nullable();
            $table->string('residence_city_code', 20)->nullable();
            $table->string('residence_city_name', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_secondary', 30)->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->string('sex', 5)->nullable();
            $table->decimal('salary', 14, 2)->nullable();
            $table->string('education_level', 50)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->string('email', 100)->nullable();
            $table->string('linkage_type', 30)->nullable();
            $table->string('contributor_type', 30)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('employment_status', 20)->default('activo');
            $table->string('work_center_name', 150)->nullable();
            $table->string('cost_center_code', 50)->nullable();
            $table->string('cost_center_name', 150)->nullable();
            $table->string('position_code', 50)->nullable();
            $table->string('position_name', 150)->nullable();
            $table->string('salary_scale', 30)->nullable();
            $table->string('salary_type_code', 50)->nullable();
            $table->string('salary_type_name', 100)->nullable();
            $table->string('contract_type_code', 50)->nullable();
            $table->string('contract_type_name', 100)->nullable();
            $table->string('eps_code', 50)->nullable();
            $table->string('eps_name', 150)->nullable();
            $table->string('afp_code', 50)->nullable();
            $table->string('afp_name', 150)->nullable();
            $table->string('arp_name', 150)->nullable();
            $table->string('risk_level', 20)->nullable();
            $table->string('compensation_fund_name', 150)->nullable();
            $table->string('bank_code', 50)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('account_type', 10)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('payment_method_code', 50)->nullable();
            $table->string('economic_activity_code', 50)->nullable();
            $table->string('economic_activity_name', 150)->nullable();
            $table->json('payroll_extra')->nullable();
            $table->timestamps();

            $table->unique('document_number', 'emp_ficha_profiles_doc_uq');
            $table->unique('personal_requisition_ficha_entry_id', 'emp_ficha_profiles_entry_uq');
            $table->index('employment_status');

            $table->foreign('personal_requisition_ficha_entry_id', 'emp_ficha_profiles_entry_fk')
                ->references('id')
                ->on('personal_requisition_ficha_entries')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_ficha_profiles');
        Schema::dropIfExists('requisition_position_payroll_maps');
        Schema::dropIfExists('payroll_catalog_items');
    }
};
