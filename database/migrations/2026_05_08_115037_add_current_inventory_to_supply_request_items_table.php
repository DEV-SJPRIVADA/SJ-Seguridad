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
            $table->integer('current_inventory')->default(0)->after('supply_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->dropColumn('current_inventory');
        });
    }
};
