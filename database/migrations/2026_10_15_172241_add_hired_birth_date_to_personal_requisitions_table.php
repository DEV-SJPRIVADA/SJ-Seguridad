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
            $table->date('hired_birth_date')->nullable()->after('hired_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_requisitions', function (Blueprint $table): void {
            $table->dropColumn('hired_birth_date');
        });
    }
};
