<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_managed_areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('area_key');
            $table->timestamps();

            $table->unique(['user_id', 'area_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_managed_areas');
    }
};
