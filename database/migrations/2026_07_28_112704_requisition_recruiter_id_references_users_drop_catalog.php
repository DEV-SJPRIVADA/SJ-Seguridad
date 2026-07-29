<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->dropForeign(['recruiter_id']);
        });

        DB::table('personal_requisitions')->update(['recruiter_id' => null]);

        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->foreign('recruiter_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::dropIfExists('requisition_recruiters');
    }

    public function down(): void
    {
        Schema::create('requisition_recruiters', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->dropForeign(['recruiter_id']);
        });

        DB::table('personal_requisitions')->update(['recruiter_id' => null]);

        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->foreign('recruiter_id')->references('id')->on('requisition_recruiters')->nullOnDelete();
        });
    }
};
