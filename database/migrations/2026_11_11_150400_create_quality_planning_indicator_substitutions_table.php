<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_planning_indicator_substitutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('indicator_id')->constrained('quality_planning_indicators')->cascadeOnDelete();
            $table->unsignedBigInteger('holder_user_id');
            $table->unsignedBigInteger('substitute_user_id');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamps();

            // Nombres cortos: MySQL limita los identificadores a 64 caracteres.
            $table->foreign('holder_user_id', 'qp_substitutions_holder_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('substitute_user_id', 'qp_substitutions_substitute_fk')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'qp_substitutions_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['indicator_id', 'substitute_user_id'], 'qp_substitutions_indicator_substitute_index');
            $table->index(['starts_on', 'ends_on'], 'qp_substitutions_range_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_planning_indicator_substitutions');
    }
};
