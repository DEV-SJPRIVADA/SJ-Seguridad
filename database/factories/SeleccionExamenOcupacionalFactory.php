<?php

namespace Database\Factories;

use App\Models\CommercialClient;
use App\Models\PayrollCatalogItem;
use App\Models\SeleccionExamenOcupacional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeleccionExamenOcupacional>
 */
class SeleccionExamenOcupacionalFactory extends Factory
{
    protected $model = SeleccionExamenOcupacional::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = $this->ensureCatalogItem('city', 'CALI', 'Cali');
        $position = $this->ensureCatalogItem('position', 'VIG', 'Vigilante');
        $eps = $this->ensureCatalogItem('eps', 'SURA', 'Sura');
        $afp = $this->ensureCatalogItem('afp', 'PROTECCION', 'Protección');
        $marital = $this->ensureCatalogItem('marital_status', 'SOLTERO', 'Soltero');
        $solicitud = $this->ensureCatalogItem('seleccion_solicitud_status', 'EN_PROCESO', 'EN PROCESO');

        $client = CommercialClient::query()->firstOrCreate(
            ['nit' => '900999002-2'],
            ['name' => 'Cliente Examen Test SAS', 'city' => 'Cali'],
        );

        $responsable = User::factory()->create([
            'is_active' => true,
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);

        return [
            'document_number' => fake()->unique()->numerify('##########'),
            'full_name' => fake()->name(),
            'position_code' => $position->code,
            'position_name' => $position->name,
            'servicio_sector' => 'Vigilancia urbana',
            'commercial_client_id' => $client->id,
            'eps_code' => $eps->code,
            'eps_name' => $eps->name,
            'afp_code' => $afp->code,
            'afp_name' => $afp->name,
            'birth_date' => fake()->date(),
            'city_code' => $city->code,
            'city_name' => $city->name,
            'address' => fake()->streetAddress(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('3##########'),
            'marital_status_code' => $marital->code,
            'marital_status_name' => $marital->name,
            'fecha_arl' => fake()->date(),
            'solicitud_status_code' => $solicitud->code,
            'solicitud_status_name' => $solicitud->name,
            'responsable_user_id' => $responsable->id,
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
