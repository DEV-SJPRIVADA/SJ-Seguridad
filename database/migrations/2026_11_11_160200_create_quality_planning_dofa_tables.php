<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DOFA + objetivos + estrategias de Planeación estratégica (FEAT-051).
 * Periodos libres: se guardan como rango de fechas, no como año fijo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_planning_dofas', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->string('period_label', 80);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('draft')->index();
            $table->text('summary')->nullable();
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('created_by_user_id', 'qp_dofas_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['starts_on', 'ends_on'], 'qp_dofas_range_index');
        });

        Schema::create('quality_planning_dofa_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dofa_id')->constrained('quality_planning_dofas')->cascadeOnDelete();
            $table->string('quadrant', 16)->index();
            $table->text('description');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['dofa_id', 'quadrant'], 'qp_dofa_items_dofa_quadrant_index');
        });

        Schema::create('quality_planning_objectives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dofa_id')->constrained('quality_planning_dofas')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['dofa_id', 'sort'], 'qp_objectives_dofa_sort_index');
        });

        Schema::create('quality_planning_strategies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('objective_id')->constrained('quality_planning_objectives')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('responsible', 160)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedSmallInteger('progress')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['objective_id', 'sort'], 'qp_strategies_objective_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_planning_strategies');
        Schema::dropIfExists('quality_planning_objectives');
        Schema::dropIfExists('quality_planning_dofa_items');
        Schema::dropIfExists('quality_planning_dofas');
    }
};
