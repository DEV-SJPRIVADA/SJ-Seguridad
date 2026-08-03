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
    }

    public function down(): void
    {
        $codes = collect(config('employee_ficha.document_type_defaults', []))
            ->pluck('code')
            ->filter()
            ->all();

        if ($codes === []) {
            return;
        }

        PayrollCatalogItem::query()
            ->where('catalog_type', 'document_type')
            ->whereIn('code', $codes)
            ->delete();
    }
};
