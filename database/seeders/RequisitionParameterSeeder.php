<?php

namespace Database\Seeders;

use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionContractType;
use App\Models\RequisitionUniform;
use Illuminate\Database\Seeder;

class RequisitionParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedCatalog(RequisitionPosition::class, [
            'Vigilante de seguridad',
            'Supervisor de puesto',
        ]);

        $this->seedCatalog(RequisitionRequestReason::class, [
            'Cargo nuevo',
            'Reemplazo',
            'Servicio nuevo',
        ]);

        $this->seedCatalog(RequisitionClient::class, [
            'Constructora Solanillas SAS',
            'Cliente interno SJ Seguridad',
        ]);

        $this->seedCatalog(RequisitionCity::class, [
            'Cali',
            'Puerto Tejada',
        ]);

        $this->seedCatalog(RequisitionClientType::class, [
            'Externo',
            'Interno',
        ]);

        $this->seedCatalog(RequisitionProgrammingType::class, [
            '5x2',
            '2x2x2',
        ]);

        $this->seedCatalog(RequisitionUniform::class, [
            'Camisa + Pantalón + Botas',
            'Overol + Botas',
            'Traje Formal',
            'Sin Dotación',
        ]);

        $this->seedCatalog(RequisitionContractType::class, [
            'Fijo',
            'Indefinido',
            'Obra o Labor',
            'Aprendizaje',
        ]);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  array<int, string>  $items
     */
    private function seedCatalog(string $modelClass, array $items): void
    {
        foreach ($items as $index => $name) {
            $modelClass::query()->firstOrCreate(
                ['name' => $name],
                [
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
