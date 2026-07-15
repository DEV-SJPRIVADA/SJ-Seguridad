<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('indicator_system_documents')) {
            return;
        }

        Schema::create('indicator_system_documents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('scope', 30);
            $table->foreignId('indicator_id')->nullable()->constrained('indicators')->nullOnDelete();
            $table->foreignId('current_version_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('indicator_system_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_system_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status', 20)->default('draft');
            $table->longText('content');
            $table->text('change_summary')->nullable();
            $table->text('change_reason')->nullable();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['indicator_system_document_id', 'version_number'], 'indicator_doc_versions_unique');
        });

        Schema::table('indicator_system_documents', function (Blueprint $table) {
            $table->foreign('current_version_id')
                ->references('id')
                ->on('indicator_system_document_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('indicator_system_documents', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('indicator_system_document_versions');
        Schema::dropIfExists('indicator_system_documents');
    }
};
