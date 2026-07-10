<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicator_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('status', 20)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicator_periods');
    }
};
