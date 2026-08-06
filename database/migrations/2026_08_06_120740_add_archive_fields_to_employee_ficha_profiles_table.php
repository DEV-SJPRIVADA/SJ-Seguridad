<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table): void {
            $table->string('archive_shelf', 100)->nullable()->after('payroll_extra');
            $table->string('archive_box', 100)->nullable()->after('archive_shelf');
        });
    }

    public function down(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table): void {
            $table->dropColumn(['archive_shelf', 'archive_box']);
        });
    }
};
