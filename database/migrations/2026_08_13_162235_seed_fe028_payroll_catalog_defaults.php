<?php

use App\Models\PayrollCatalogItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('employee_ficha.document_type_defaults', []) as $index => $item) {
            PayrollCatalogItem::query()->updateOrCreate(
                [
                    'catalog_type' => 'document_type',
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'sort_order' => $item['sort_order'] ?? ($index + 1),
                    'is_active' => true,
                ]
            );
        }

        foreach (config('employee_ficha.catalog_static_defaults', []) as $catalogType => $items) {
            foreach ($items as $index => $item) {
                PayrollCatalogItem::query()->updateOrCreate(
                    [
                        'catalog_type' => $catalogType,
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
    }

    public function down(): void
    {
        $documentCodes = collect(config('employee_ficha.document_type_defaults', []))
            ->pluck('code')
            ->filter()
            ->all();

        if ($documentCodes !== []) {
            PayrollCatalogItem::query()
                ->where('catalog_type', 'document_type')
                ->whereIn('code', $documentCodes)
                ->delete();
        }

        foreach (config('employee_ficha.catalog_static_defaults', []) as $catalogType => $items) {
            $codes = collect($items)->pluck('code')->filter()->all();

            if ($codes === []) {
                continue;
            }

            PayrollCatalogItem::query()
                ->where('catalog_type', $catalogType)
                ->whereIn('code', $codes)
                ->delete();
        }
    }
};
