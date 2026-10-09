<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table): void {
            if (! Schema::hasColumn('employee_ficha_profiles', 'requires_psicofisicos')) {
                $table->boolean('requires_psicofisicos')->default(true)->after('requires_acreditacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_ficha_profiles', function (Blueprint $table): void {
            if (Schema::hasColumn('employee_ficha_profiles', 'requires_psicofisicos')) {
                $table->dropColumn('requires_psicofisicos');
            }
        });
    }
};
