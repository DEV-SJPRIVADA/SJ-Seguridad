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
            // Campos adicionales según requerimiento visual
            $table->string('contract_type')->nullable()->after('replacement_name');
            $table->string('contract_duration')->nullable()->after('contract_type');
            $table->decimal('base_salary', 12, 2)->nullable()->after('contract_duration');
            $table->decimal('transport_allowance', 12, 2)->nullable()->after('base_salary');
            $table->decimal('mobility_allowance', 12, 2)->nullable()->after('transport_allowance');
            $table->decimal('statutory_bonus', 12, 2)->nullable()->after('mobility_allowance');
            $table->decimal('non_statutory_bonus', 12, 2)->nullable()->after('statutory_bonus');
            $table->decimal('other_allowances', 12, 2)->nullable()->after('non_statutory_bonus');
            $table->string('leasing_contract')->nullable()->after('other_allowances');
            
            // Campos de gestión GH
            $table->string('recruiter_name')->nullable()->after('human_resources_observation');
            $table->unsignedSmallInteger('hired_quantity')->default(0)->after('recruiter_name');
            $table->date('hiring_date')->nullable()->after('hired_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->dropColumn([
                'contract_type',
                'contract_duration',
                'base_salary',
                'transport_allowance',
                'mobility_allowance',
                'statutory_bonus',
                'non_statutory_bonus',
                'other_allowances',
                'leasing_contract',
                'recruiter_name',
                'hired_quantity',
                'hiring_date',
            ]);
        });
    }
};
