<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_requisition_ficha_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('personal_requisition_id');
            $table->string('hired_document', 50);
            $table->string('hired_full_name', 255);
            $table->timestamp('moved_to_ficha_at')->nullable();
            $table->unsignedBigInteger('moved_to_ficha_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique('personal_requisition_id', 'req_ficha_entries_req_id_uq');
            $table->index('hired_document');

            $table->foreign('personal_requisition_id', 'req_ficha_entries_req_id_fk')
                ->references('id')
                ->on('personal_requisitions')
                ->cascadeOnDelete();
            $table->foreign('moved_to_ficha_by', 'req_ficha_entries_moved_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('created_by', 'req_ficha_entries_created_by_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_requisition_ficha_entries');
    }
};
