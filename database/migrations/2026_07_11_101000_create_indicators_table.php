<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('unit', 30)->default('percentage');
            $table->decimal('target_value', 8, 2);
            $table->string('target_operator', 2);
            $table->string('frequency', 20)->default('monthly');
            $table->text('formula_description');
            $table->json('required_fields');
            $table->boolean('allows_over_100')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
