<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una OC puede referenciar una cotización aprobada (opcional). La cotización
     * queda consumida por esa orden: no puede vincularse a otra.
     */
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite): void {
            if ($isSqlite) {
                $table->unsignedBigInteger('purchase_quotation_id')->nullable()->unique();
            } else {
                $table->foreignId('purchase_quotation_id')
                    ->nullable()
                    ->unique()
                    ->constrained('purchase_quotations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('purchase_orders', function (Blueprint $table) use ($isSqlite): void {
            if (! $isSqlite) {
                $table->dropForeign(['purchase_quotation_id']);
            }

            $table->dropUnique(['purchase_quotation_id']);
            $table->dropColumn('purchase_quotation_id');
        });
    }
};
