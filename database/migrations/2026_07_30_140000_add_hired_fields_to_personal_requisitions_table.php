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
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->string('hired_document', 50)->nullable()->after('hiring_date');
            $table->string('hired_full_name', 255)->nullable()->after('hired_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->dropColumn(['hired_document', 'hired_full_name']);
        });
    }
};
