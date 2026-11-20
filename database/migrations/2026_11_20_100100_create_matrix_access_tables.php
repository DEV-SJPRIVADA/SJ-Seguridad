<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrix_user_access', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('matrix_id')->constrained('matrices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('can_register')->default(false);
            $table->boolean('can_view_others')->default(false);
            $table->boolean('can_edit_others')->default(false);
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['matrix_id', 'user_id'], 'mx_user_access_matrix_user_unique');
        });

        Schema::create('matrix_area_access', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('matrix_id')->constrained('matrices')->cascadeOnDelete();
            $table->string('area_key', 40);
            $table->boolean('can_register')->default(false);
            $table->boolean('can_view_others')->default(false);
            $table->boolean('can_edit_others')->default(false);
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['matrix_id', 'area_key'], 'mx_area_access_matrix_area_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrix_area_access');
        Schema::dropIfExists('matrix_user_access');
    }
};
