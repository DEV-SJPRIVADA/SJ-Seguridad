<?php

namespace Database\Factories;

use App\Models\CommercialClient;
use App\Models\PayrollCatalogItem;
use App\Models\RequisitionUniform;
use App\Models\SeleccionIngreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeleccionIngreso>
 */
class SeleccionIngresoFactory extends Factory
{
    protected $model = SeleccionIngreso::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = $this->ensureCatalogItem('city', 'CALI', 'Cali');
        $position = $this->ensureCatalogItem('position', 'VIG', 'Vigilante');
        $blood = $this->ensureCatalogItem('blood_type', 'O+', 'O+');

        $client = CommercialClient::query()->firstOrCreate(
            ['nit' => '900999001-1'],
            ['name' => 'Cliente Selección Test SAS', 'city' => 'Cali'],
        );

        $uniform = RequisitionUniform::query()->first()
            ?? RequisitionUniform::query()->create([
                'name' => 'Dotación test',
                'is_active' => true,
                'sort_order' => 1,
            ]);

        $responsable = User::factory()->create([
            'is_active' => true,
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);

        return [
            'document_number' => fake()->unique()->numerify('##########'),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('3##########'),
            'city_code' => $city->code,
            'city_name' => $city->name,
            'position_code' => $position->code,
            'position_name' => $position->name,
            'commercial_client_id' => $client->id,
            'shirt_size' => 'M',
            'pants_size' => '32',
            'shoes_size' => '40',
            'requisition_uniform_id' => $uniform->id,
            'fecha_ingreso' => fake()->date(),
            'blood_type_code' => $blood->code,
            'blood_type_name' => $blood->name,
            'reemplaza_a' => 'N/A',
            'responsable_user_id' => $responsable->id,
            'referido' => 'N/A',
            'jefe_ope' => fake()->name(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    private function ensureCatalogItem(string $type, string $code, string $name): PayrollCatalogItem
    {
        return PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => $type, 'code' => $code],
            ['name' => $name, 'is_active' => true, 'sort_order' => 1],
        );
    }
}
