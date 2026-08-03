<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table): void {
            $table->string('linkage_type', 100)->nullable()->change();
            $table->string('contributor_type', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table): void {
            $table->string('linkage_type', 30)->nullable()->change();
            $table->string('contributor_type', 30)->nullable()->change();
        });
    }
};
