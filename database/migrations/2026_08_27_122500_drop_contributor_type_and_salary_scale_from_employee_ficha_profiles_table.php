<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table) {
            $table->dropColumn(['contributor_type', 'salary_scale']);
        });
    }

    public function down(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table) {
            $table->string('contributor_type', 50)->nullable()->after('linkage_type');
            $table->string('salary_scale', 30)->nullable()->after('position_name');
        });
    }
};
