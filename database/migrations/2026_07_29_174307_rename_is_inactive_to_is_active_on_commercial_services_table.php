<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commercial_services')) {
            return;
        }

        if (! Schema::hasColumn('commercial_services', 'is_active')) {
            Schema::table('commercial_services', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true)->after('portfolio');
            });
        }

        if (Schema::hasColumn('commercial_services', 'is_inactive')) {
            DB::statement('UPDATE commercial_services SET is_active = CASE WHEN is_inactive = 1 THEN 0 ELSE 1 END');

            Schema::table('commercial_services', function (Blueprint $table): void {
                $table->dropColumn('is_inactive');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('commercial_services')) {
            return;
        }

        if (! Schema::hasColumn('commercial_services', 'is_inactive')) {
            Schema::table('commercial_services', function (Blueprint $table): void {
                $table->boolean('is_inactive')->default(false)->after('portfolio');
            });
        }

        if (Schema::hasColumn('commercial_services', 'is_active')) {
            DB::statement('UPDATE commercial_services SET is_inactive = CASE WHEN is_active = 0 THEN 1 ELSE 0 END');

            Schema::table('commercial_services', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }
    }
};
