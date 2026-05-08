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
        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->text('purchasing_observations')->nullable()->after('unit_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->dropColumn(['purchasing_observations']);
        });
    }
};
