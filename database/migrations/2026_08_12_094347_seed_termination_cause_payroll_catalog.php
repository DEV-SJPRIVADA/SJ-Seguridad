<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('employee_ficha.termination_cause_defaults', []) as $index => $item) {
            DB::table('payroll_catalog_items')->updateOrInsert(
                [
                    'catalog_type' => 'termination_cause',
                    'code' => $item['code'],
                ],
                [
                    'name' => $item['name'],
                    'sort_order' => $item['sort_order'] ?? ($index + 1),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        $codes = collect(config('employee_ficha.termination_cause_defaults', []))
            ->pluck('code')
            ->all();

        if ($codes !== []) {
            DB::table('payroll_catalog_items')
                ->where('catalog_type', 'termination_cause')
                ->whereIn('code', $codes)
                ->delete();
        }
    }
};
