<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_ficha_employment_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('personal_requisition_ficha_entry_id');
            $table->unsignedBigInteger('personal_requisition_id')->nullable();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('status', 20)->default('activo');
            $table->date('hire_date')->nullable();
            $table->decimal('salary', 14, 2)->nullable();
            $table->string('position_code', 50)->nullable();
            $table->string('position_name', 150)->nullable();
            $table->string('cost_center_code', 50)->nullable();
            $table->string('cost_center_name', 150)->nullable();
            $table->string('contract_type_code', 50)->nullable();
            $table->string('contract_type_name', 100)->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('work_center_name', 150)->nullable();
            $table->string('eps_code', 50)->nullable();
            $table->string('eps_name', 150)->nullable();
            $table->string('afp_code', 50)->nullable();
            $table->string('afp_name', 150)->nullable();
            $table->string('linkage_type', 100)->nullable();
            $table->string('termination_cause_code', 50)->nullable();
            $table->string('termination_cause_name', 150)->nullable();
            $table->boolean('is_rehireable')->nullable();
            $table->date('last_work_day')->nullable();
            $table->date('termination_date')->nullable();
            $table->text('termination_notes')->nullable();
            $table->string('termination_letter_type', 40)->nullable();
            $table->string('termination_letter_path', 255)->nullable();
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();

            $table->foreign('personal_requisition_ficha_entry_id', 'efep_ficha_entry_fk')
                ->references('id')
                ->on('personal_requisition_ficha_entries')
                ->cascadeOnDelete();
            $table->foreign('personal_requisition_id', 'efep_requisition_fk')
                ->references('id')
                ->on('personal_requisitions')
                ->nullOnDelete();
            $table->foreign('opened_by', 'efep_opened_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('closed_by', 'efep_closed_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['personal_requisition_ficha_entry_id', 'status'], 'efep_entry_status_idx');
            $table->index(['personal_requisition_ficha_entry_id', 'sequence'], 'efep_entry_sequence_idx');
        });

        $this->backfillExistingProfiles();
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_ficha_employment_periods');
    }

    private function backfillExistingProfiles(): void
    {
        if (! Schema::hasTable('employee_ficha_profiles') || ! Schema::hasTable('personal_requisition_ficha_entries')) {
            return;
        }

        $profiles = DB::table('employee_ficha_profiles as p')
            ->join('personal_requisition_ficha_entries as e', 'e.id', '=', 'p.personal_requisition_ficha_entry_id')
            ->whereNotNull('e.moved_to_ficha_at')
            ->select([
                'p.personal_requisition_ficha_entry_id',
                'e.personal_requisition_id',
                'p.hire_date',
                'p.salary',
                'p.position_code',
                'p.position_name',
                'p.cost_center_code',
                'p.cost_center_name',
                'p.contract_type_code',
                'p.contract_type_name',
                'p.contract_end_date',
                'p.work_center_name',
                'p.eps_code',
                'p.eps_name',
                'p.afp_code',
                'p.afp_name',
                'p.linkage_type',
                'p.employment_status',
                'p.termination_date',
                'e.moved_to_ficha_by',
                'p.created_at',
                'p.updated_at',
            ])
            ->get();

        foreach ($profiles as $profile) {
            $isActive = ($profile->employment_status ?? 'activo') === 'activo'
                && ($profile->termination_date === null || $profile->termination_date > now()->toDateString());

            DB::table('employee_ficha_employment_periods')->insert([
                'personal_requisition_ficha_entry_id' => $profile->personal_requisition_ficha_entry_id,
                'personal_requisition_id' => $profile->personal_requisition_id,
                'sequence' => 1,
                'status' => $isActive ? 'activo' : 'cerrado',
                'hire_date' => $profile->hire_date,
                'salary' => $profile->salary,
                'position_code' => $profile->position_code,
                'position_name' => $profile->position_name,
                'cost_center_code' => $profile->cost_center_code,
                'cost_center_name' => $profile->cost_center_name,
                'contract_type_code' => $profile->contract_type_code,
                'contract_type_name' => $profile->contract_type_name,
                'contract_end_date' => $profile->contract_end_date,
                'work_center_name' => $profile->work_center_name,
                'eps_code' => $profile->eps_code,
                'eps_name' => $profile->eps_name,
                'afp_code' => $profile->afp_code,
                'afp_name' => $profile->afp_name,
                'linkage_type' => $profile->linkage_type,
                'termination_date' => $isActive ? null : $profile->termination_date,
                'opened_by' => $profile->moved_to_ficha_by,
                'created_at' => $profile->created_at ?? now(),
                'updated_at' => $profile->updated_at ?? now(),
            ]);
        }
    }
};
