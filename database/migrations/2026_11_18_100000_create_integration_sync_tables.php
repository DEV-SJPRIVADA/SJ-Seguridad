<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_sync_state', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->timestamp('last_watermark')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_status', 20)->default('never');
            $table->unsignedInteger('last_sent')->default(0);
            $table->unsignedInteger('last_failed')->default(0);
            $table->timestamps();
        });

        Schema::create('integration_sync_log', function (Blueprint $table): void {
            $table->id();
            $table->string('mode', 20);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 20);
            $table->unsignedInteger('records_sent')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->unsignedInteger('batches')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_sync_log');
        Schema::dropIfExists('integration_sync_state');
    }
};
