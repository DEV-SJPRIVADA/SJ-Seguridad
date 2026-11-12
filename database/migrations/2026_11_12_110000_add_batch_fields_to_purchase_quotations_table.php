<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrupa las cotizaciones por lote: cada lote representa una OC futura y se envía
     * completo a un director, que elige una cotización ganadora por lote.
     */
    public function up(): void
    {
        Schema::table('purchase_quotations', function (Blueprint $table) {
            $table->string('lote', 36)->nullable()->after('purchase_request_id');
            $table->string('lote_label', 120)->nullable()->after('lote');
            $table->unsignedSmallInteger('lote_orden')->default(1)->after('lote_label');
            $table->boolean('seleccionada')->default(false)->after('resuelta_at');

            $table->index(['purchase_request_id', 'lote'], 'pq_request_lote_idx');
        });

        // Las cotizaciones anteriores al agrupamiento quedan como lotes de una sola pieza.
        DB::table('purchase_quotations')
            ->whereNull('lote')
            ->orderBy('id')
            ->get(['id', 'description', 'original_name'])
            ->each(function (object $quotation): void {
                DB::table('purchase_quotations')->where('id', $quotation->id)->update([
                    'lote' => 'legacy-'.$quotation->id,
                    'lote_label' => $quotation->description ?: $quotation->original_name,
                    'lote_orden' => 1,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('purchase_quotations', function (Blueprint $table) {
            $table->dropIndex('pq_request_lote_idx');
            $table->dropColumn(['lote', 'lote_label', 'lote_orden', 'seleccionada']);
        });
    }
};
