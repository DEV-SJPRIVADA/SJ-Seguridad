<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_improvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_capture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operations_leader_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('indicator_periods')->cascadeOnDelete();
            $table->longText('analysis');
            $table->longText('action_taken');
            $table->longText('action_defined');
            $table->longText('improvement_required')->nullable();
            $table->longText('integrated_analysis_block')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('indicator_capture_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_improvements');
    }
};
