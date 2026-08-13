<?php

namespace Tests\Support;

use App\Models\PayrollCatalogItem;

trait EmployeeFichaMasivosPayload
{
    protected function seedFe028CatalogFixtures(): void
    {
        $items = [
            ['position', 'VIG', 'Vigilante', 1],
            ['cost_center', 'CC01', 'Centro Costo Test', 1],
            ['eps', 'EPS01', 'EPS Test', 1],
            ['afp', 'AFP01', 'AFP Test', 1],
            ['ccf', 'CCF01', 'Caja Compensacion Test', 1],
            ['bank', 'B01', 'Banco Test', 1],
            ['payment_method', '1', 'Transferencia', 1],
            ['work_center', 'WC01', 'Centro Trabajo Test', 1],
        ];

        foreach ($items as [$type, $code, $name, $sortOrder]) {
            PayrollCatalogItem::query()->firstOrCreate(
                ['catalog_type' => $type, 'code' => $code],
                ['name' => $name, 'sort_order' => $sortOrder, 'is_active' => true],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function masivosCorePayload(array $overrides = []): array
    {
        return array_merge([
            'sex' => 'M',
            'hire_date' => now()->subMonth()->toDateString(),
            'position_code' => 'VIG',
            'salary' => '2500000',
            'cost_center_code' => 'CC01',
            'eps_code' => 'EPS01',
            'afp_code' => 'AFP01',
            'bank_code' => 'B01',
            'account_type' => '1',
            'account_number' => '1234567890',
            'payment_method_code' => '1',
            'payroll_extra' => [
                'ccf_code' => 'CCF01',
            ],
        ], $overrides);
    }
}
