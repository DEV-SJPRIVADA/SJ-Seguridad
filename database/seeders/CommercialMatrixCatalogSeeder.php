<?php

namespace Database\Seeders;

use App\Models\CommercialClientType;
use App\Models\CommercialSector;
use App\Models\CommercialServiceType;
use Illuminate\Database\Seeder;

class CommercialMatrixCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = ['INDUSTRIAL', 'COMERCIAL', 'SALUD', 'SERVICIOS', 'CONSTRUCCION', 'RESIDENCIAL', 'DEPORTIVO'];
        foreach ($sectors as $i => $name) {
            CommercialSector::query()->firstOrCreate(
                ['name' => $name],
                ['is_active' => true, 'sort_order' => $i + 1]
            );
        }

        $clientTypes = ['EXTERNO', 'INTERNO', 'GRUPO', 'EXTERNO - TARIFA GRUPO'];
        foreach ($clientTypes as $i => $name) {
            CommercialClientType::query()->firstOrCreate(
                ['name' => $name],
                ['is_active' => true, 'sort_order' => $i + 1]
            );
        }

        $serviceTypes = ['VIGILANCIA', 'ESCOLTA', 'MONITOREO', 'GPS', 'GUARDA'];
        foreach ($serviceTypes as $i => $name) {
            CommercialServiceType::query()->firstOrCreate(
                ['name' => $name],
                ['is_active' => true, 'sort_order' => $i + 1]
            );
        }
    }
}
