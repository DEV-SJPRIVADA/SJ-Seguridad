<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_request_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('development_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['development_request_id', 'created_at'], 'drsl_request_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_request_status_logs');
    }
};
