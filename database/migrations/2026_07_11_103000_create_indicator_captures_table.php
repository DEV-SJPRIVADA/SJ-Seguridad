<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operations_leader_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('indicator_periods')->cascadeOnDelete();
            $table->json('input_data');
            $table->decimal('numerator', 14, 2);
            $table->decimal('denominator', 14, 2);
            $table->decimal('result_percentage', 8, 2);
            $table->boolean('complies');
            $table->longText('analysis_text')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['indicator_id', 'operations_leader_id', 'period_id'], 'indicator_captures_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_captures');
    }
};
