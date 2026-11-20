<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrix_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('matrix_id')->constrained('matrices')->cascadeOnDelete();
            $table->foreignId('matrix_version_id')->constrained('matrix_versions')->restrictOnDelete();
            $table->string('folio', 20);
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['matrix_id', 'folio'], 'mx_submissions_matrix_folio_unique');
            $table->index(['matrix_id', 'created_by_user_id', 'status'], 'mx_submissions_matrix_creator_status_index');
            $table->index(['matrix_id', 'assigned_to_user_id'], 'mx_submissions_matrix_assignee_index');
            $table->index(['matrix_id', 'created_at'], 'mx_submissions_matrix_created_index');
        });

        Schema::create('matrix_submission_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained('matrix_submissions')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('matrix_fields')->restrictOnDelete();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 18, 4)->nullable();
            $table->date('value_date')->nullable();
            $table->boolean('value_bool')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'field_id'], 'mx_values_submission_field_unique');
        });

        Schema::create('matrix_submission_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('submission_id')->constrained('matrix_submissions')->cascadeOnDelete();
            $table->foreignId('field_id')->nullable()->constrained('matrix_fields')->restrictOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->index('submission_id', 'mx_attachments_submission_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrix_submission_attachments');
        Schema::dropIfExists('matrix_submission_values');
        Schema::dropIfExists('matrix_submissions');
    }
};
