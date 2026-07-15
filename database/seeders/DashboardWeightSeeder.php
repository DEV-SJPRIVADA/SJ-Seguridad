<?php

namespace Database\Seeders;

use App\Models\DashboardWeight;
use App\Models\Indicator;
use Illuminate\Database\Seeder;

class DashboardWeightSeeder extends Seeder
{
    public function run(): void
    {
        $weights = [
            'FT-OP-01' => 5,
            'FT-OP-02' => 15,
            'FT-OP-03' => 20,
            'FT-OP-04' => 10,
            'FT-OP-05' => 10,
            'FT-OP-06' => 0,
            'FT-OP-07' => 10,
            'FT-OP-08' => 10,
            'FT-OP-09' => 20,
        ];

        $indicators = Indicator::query()->get()->keyBy('code');

        foreach ($weights as $code => $weight) {
            if (! isset($indicators[$code])) {
                continue;
            }

            DashboardWeight::query()->updateOrCreate(
                ['indicator_id' => $indicators[$code]->id],
                ['weight' => $weight]
            );
        }
    }
}
