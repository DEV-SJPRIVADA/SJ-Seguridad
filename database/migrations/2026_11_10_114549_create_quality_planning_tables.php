<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_planning_indicators', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->index();
            $table->unsignedBigInteger('current_published_version_id')->nullable();
            $table->timestamps();
        });

        Schema::create('quality_planning_indicator_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_id')->constrained('quality_planning_indicators')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status', 20)->index();
            $table->string('name');
            $table->text('objective')->nullable();
            $table->string('process_key', 80)->nullable();
            $table->string('unit', 30)->default('percentage');
            $table->string('frequency', 20)->default('monthly');
            $table->decimal('target_value', 8, 2);
            $table->string('target_operator', 4)->default('>=');
            $table->decimal('critical_value', 8, 2)->nullable();
            $table->text('formula');
            $table->json('fields');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['indicator_id', 'version_number'], 'qp_indicator_versions_unique');
        });

        Schema::create('quality_planning_indicator_assignees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_id')->constrained('quality_planning_indicators')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('can_capture')->default(true);
            $table->boolean('can_consult')->default(false);
            $table->timestamps();
            $table->unique(['indicator_id', 'user_id'], 'qp_indicator_assignees_unique');
        });

        Schema::create('quality_planning_captures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_version_id')->constrained('quality_planning_indicator_versions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->json('input_data');
            $table->decimal('result_value', 14, 4)->nullable();
            $table->boolean('complies')->default(false);
            $table->text('analysis_text')->nullable();
            $table->text('improvement_required')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['indicator_version_id', 'user_id', 'year', 'month'], 'qp_captures_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_planning_captures');
        Schema::dropIfExists('quality_planning_indicator_assignees');
        Schema::dropIfExists('quality_planning_indicator_versions');
        Schema::dropIfExists('quality_planning_indicators');
    }
};
