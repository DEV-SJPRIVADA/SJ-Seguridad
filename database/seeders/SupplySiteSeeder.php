<?php

namespace Database\Seeders;

use App\Models\SupplySite;
use Illuminate\Database\Seeder;

class SupplySiteSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'name' => 'cali_central',
                'utilization' => 'Sede Principal y central de monitoreo',
                'city' => 'Cali',
            ],
            [
                'name' => 'cartagena',
                'utilization' => 'Sede Cartagena',
                'city' => 'Cartagena',
            ],
            [
                'name' => 'manizales',
                'utilization' => 'Sede Manizales',
                'city' => 'Manizales',
            ],
        ];

        foreach ($sites as $site) {
            SupplySite::query()->updateOrCreate(
                ['name' => $site['name']],
                [
                    'utilization' => $site['utilization'],
                    'city' => $site['city'],
                    'is_active' => true,
                ]
            );
        }
    }
}
