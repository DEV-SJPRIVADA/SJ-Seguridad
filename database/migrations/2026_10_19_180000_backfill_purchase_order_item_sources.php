<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaza las líneas de OC ya emitidas con su línea de origen.
 *
 * Hasta ahora `purchase_order_items.source_item_type` / `source_item_id` quedaban
 * en NULL, lo que impedía calcular el saldo pendiente por línea (reparto de una
 * solicitud entre varias OC). El enlace se hace por posición: la N-ésima línea
 * de la OC corresponde a la N-ésima línea del origen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_order_items') || ! Schema::hasTable('purchase_orders')) {
            return;
        }

        $orders = DB::table('purchase_orders')
            ->select(['id', 'source_type', 'source_id'])
            ->whereIn('source_type', ['purchase_request', 'supply_request'])
            ->get();

        foreach ($orders as $order) {
            $isPurchase = $order->source_type === 'purchase_request';
            $sourceTable = $isPurchase ? 'purchase_request_items' : 'supply_request_items';
            $sourceItemType = $isPurchase ? 'purchase_request_item' : 'supply_request_item';

            if (! Schema::hasTable($sourceTable)) {
                continue;
            }

            $items = DB::table('purchase_order_items')
                ->where('purchase_order_id', $order->id)
                ->orderBy('orden')
                ->orderBy('id')
                ->get();

            if ($items->isEmpty()) {
                continue;
            }

            $sourceItems = DB::table($sourceTable)
                ->where($isPurchase ? 'purchase_request_id' : 'supply_request_id', $order->source_id)
                ->orderBy('id')
                ->get()
                ->values();

            foreach ($items->values() as $position => $item) {
                $sourceItem = $sourceItems->get($position);

                if ($sourceItem === null) {
                    continue;
                }

                DB::table('purchase_order_items')
                    ->where('id', $item->id)
                    ->whereNull('source_item_type')
                    ->update([
                        'source_item_type' => $sourceItemType,
                        'source_item_id' => $sourceItem->id,
                    ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('purchase_order_items')
            ->whereNotNull('source_item_type')
            ->update(['source_item_type' => null, 'source_item_id' => null]);
    }
};
