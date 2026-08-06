<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_archive_consultations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('document_numbers');
            $table->json('consultation_types');
            $table->unsignedSmallInteger('documents_requested')->default(0);
            $table->unsignedSmallInteger('documents_matched')->default(0);
            $table->json('documents_not_found')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_archive_consultations');
    }
};
