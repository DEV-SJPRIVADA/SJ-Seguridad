<?php

use App\Models\PayrollCatalogItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $items = config('seleccion.catalog_seed_defaults.ingreso_estado', []);

        foreach ($items as $index => $item) {
            PayrollCatalogItem::query()->updateOrCreate(
                [
                    'catalog_type' => 'ingreso_estado',
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'sort_order' => $item['sort_order'] ?? ($index + 1),
                    'is_active' => true,
                ]
            );
        }
    }

    public function down(): void
    {
        $items = config('seleccion.catalog_seed_defaults.ingreso_estado', []);
        $codes = collect($items)->pluck('code')->filter()->all();

        if ($codes === []) {
            return;
        }

        PayrollCatalogItem::query()
            ->where('catalog_type', 'ingreso_estado')
            ->whereIn('code', $codes)
            ->delete();
    }
};
