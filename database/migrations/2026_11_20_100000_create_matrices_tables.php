<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrices', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 40)->nullable()->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('area_key', 40)->nullable()->index();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedInteger('submission_counter')->default(0);
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('matrix_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('matrix_id')->constrained('matrices')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['matrix_id', 'version_number'], 'mx_versions_matrix_version_unique');
        });

        Schema::create('matrix_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('matrix_version_id')->constrained('matrix_versions')->cascadeOnDelete();
            $table->string('field_key', 60);
            $table->string('label', 160);
            $table->string('type', 30)->index();
            $table->string('section', 120)->nullable();
            $table->string('help', 255)->nullable();
            $table->string('placeholder', 160)->nullable();
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->json('rules')->nullable();
            $table->string('default_value', 255)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedTinyInteger('col_span')->default(12);
            $table->timestamps();

            $table->unique(['matrix_version_id', 'field_key'], 'mx_fields_version_key_unique');
            $table->index(['matrix_version_id', 'sort'], 'mx_fields_version_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrix_fields');
        Schema::dropIfExists('matrix_versions');
        Schema::dropIfExists('matrices');
    }
};
