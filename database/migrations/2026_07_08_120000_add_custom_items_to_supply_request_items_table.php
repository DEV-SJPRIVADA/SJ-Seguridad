<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');

            Schema::create('supply_request_items_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supply_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supply_product_id')->nullable()->constrained('supply_products')->cascadeOnDelete();
                $table->string('custom_product_name')->nullable();
                $table->boolean('is_not_in_catalog')->default(false);
                $table->integer('current_inventory')->default(0);
                $table->integer('requested_quantity');
                $table->integer('approved_quantity')->nullable();
                $table->decimal('unit_cost', 15, 2)->nullable();
                $table->text('purchasing_observations')->nullable();
                $table->timestamps();
            });

            DB::statement('INSERT INTO supply_request_items_new (
                id, supply_request_id, supply_product_id, custom_product_name, is_not_in_catalog,
                current_inventory, requested_quantity, approved_quantity, unit_cost, purchasing_observations,
                created_at, updated_at
            )
            SELECT
                id, supply_request_id, supply_product_id, NULL, 0,
                current_inventory, requested_quantity, approved_quantity, unit_cost, purchasing_observations,
                created_at, updated_at
            FROM supply_request_items');

            Schema::drop('supply_request_items');
            Schema::rename('supply_request_items_new', 'supply_request_items');

            DB::statement('PRAGMA foreign_keys=ON');

            return;
        }

        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->string('custom_product_name')->nullable()->after('supply_product_id');
            $table->boolean('is_not_in_catalog')->default(false)->after('custom_product_name');
        });

        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->dropForeign(['supply_product_id']);
        });

        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->unsignedBigInteger('supply_product_id')->nullable()->change();
            $table->foreign('supply_product_id')->references('id')->on('supply_products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('supply_request_items', function (Blueprint $table) {
            $table->dropForeign(['supply_product_id']);
            $table->dropColumn(['custom_product_name', 'is_not_in_catalog']);
            $table->unsignedBigInteger('supply_product_id')->nullable(false)->change();
            $table->foreign('supply_product_id')->references('id')->on('supply_products')->cascadeOnDelete();
        });
    }
};
