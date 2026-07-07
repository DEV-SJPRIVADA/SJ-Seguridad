<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // file | link
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('external_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('quality_document_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_document_id')->constrained()->cascadeOnDelete();
            $table->string('area_key');
            $table->timestamps();

            $table->unique(['quality_document_id', 'area_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_document_areas');
        Schema::dropIfExists('quality_documents');
    }
};
