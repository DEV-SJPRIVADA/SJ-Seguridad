<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_services', function (Blueprint $table): void {
            $table->boolean('is_inactive')->default(false)->after('portfolio');
        });

        DB::table('commercial_services')
            ->where('portfolio', 'inactivos')
            ->update(['is_inactive' => true]);
    }

    public function down(): void
    {
        Schema::table('commercial_services', function (Blueprint $table): void {
            $table->dropColumn('is_inactive');
        });
    }
};
