<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_document_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['quality_document_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_document_users');
    }
};
