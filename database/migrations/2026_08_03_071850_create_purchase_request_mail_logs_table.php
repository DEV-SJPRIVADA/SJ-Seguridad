<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_mail_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->string('mail_type');
            $table->string('recipient_email');
            $table->enum('status', ['enviado', 'fallido'])->default('enviado');
            $table->text('detail')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['purchase_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_mail_logs');
    }
};
