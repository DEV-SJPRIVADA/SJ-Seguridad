<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acreditacion_export_apo_runs', function (Blueprint $table): void {
            $table->id();
            $table->date('export_date');
            $table->unsignedSmallInteger('seq');
            $table->string('file_name', 80);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vigencia_policy', 30);
            $table->boolean('include_novedades')->default(false);
            $table->unsignedInteger('rows_selected')->default(0);
            $table->unsignedInteger('rows_ok')->default(0);
            $table->unsignedInteger('rows_novedad')->default(0);
            $table->unsignedInteger('rows_blocked')->default(0);
            $table->unsignedInteger('rows_exported')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['export_date', 'seq'], 'acreditacion_export_apo_runs_date_seq_unique');
            $table->index('export_date');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acreditacion_export_apo_runs');
    }
};
