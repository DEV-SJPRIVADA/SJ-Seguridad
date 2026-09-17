<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_request_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('development_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index(['development_request_id', 'created_at'], 'drm_request_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_request_messages');
    }
};
