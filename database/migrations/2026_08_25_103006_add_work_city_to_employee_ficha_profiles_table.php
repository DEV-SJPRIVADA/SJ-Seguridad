<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table) {
            $table->string('work_city_code', 20)->nullable()->after('residence_city_name');
            $table->string('work_city_name', 100)->nullable()->after('work_city_code');
        });
    }

    public function down(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table) {
            $table->dropColumn(['work_city_code', 'work_city_name']);
        });
    }
};
