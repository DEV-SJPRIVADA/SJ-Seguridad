<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_ficha_profiles', 'requires_courses')) {
                $table->boolean('requires_courses')->default(true)->after('employment_status');
            }
            if (! Schema::hasColumn('employee_ficha_profiles', 'requires_acreditacion')) {
                $table->boolean('requires_acreditacion')->default(true)->after('requires_courses');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_ficha_profiles', 'requires_acreditacion')) {
                $table->dropColumn('requires_acreditacion');
            }
            if (Schema::hasColumn('employee_ficha_profiles', 'requires_courses')) {
                $table->dropColumn('requires_courses');
            }
        });
    }
};
