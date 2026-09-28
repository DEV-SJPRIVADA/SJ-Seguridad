<?php

namespace Database\Factories;

use App\Models\AcreditacionExportApoSetting;
use Database\Seeders\AcreditacionExportApoSettingSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcreditacionExportApoSetting>
 */
class AcreditacionExportApoSettingFactory extends Factory
{
    protected $model = AcreditacionExportApoSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return AcreditacionExportApoSettingSeeder::defaultAttributes();
    }
}
