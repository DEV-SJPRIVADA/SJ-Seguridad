<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->foreignId('sede_id')
                ->nullable()
                ->after('area_key')
                ->constrained('supply_sites')
                ->nullOnDelete();
            $table->string('site_utilization')->nullable()->after('sede_id');
            $table->string('site_city')->nullable()->after('site_utilization');
            $table->timestamp('exported_at')->nullable()->after('total_cost');
        });
    }

    public function down(): void
    {
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
            $table->dropColumn(['sede_id', 'site_utilization', 'site_city', 'exported_at']);
        });
    }
};
