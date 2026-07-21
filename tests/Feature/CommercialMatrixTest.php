<?php

namespace Tests\Feature;

use App\Models\CommercialClient;
use App\Models\CommercialService;
use App\Models\CommercialServiceType;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_user_without_permission_cannot_view_matrix(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');

        $this->actingAs($user)
            ->get(route('comercial.matriz.clients.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('comercial.matriz.services.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('comercial.dashboard'))
            ->assertForbidden();
    }

    public function test_viewer_can_list_clients_and_services_but_cannot_create(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');

        $this->actingAs($user)
            ->get(route('comercial.matriz.clients.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('comercial.matriz.services.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('comercial.matriz.clients.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('comercial.matriz.services.create'))
            ->assertForbidden();
    }

    public function test_manager_can_create_client_and_independent_services(): void
    {
        $user = $this->matrizManager();

        $response = $this->actingAs($user)->post(route('comercial.matriz.clients.store'), [
            'nit' => '901.360.444-1',
            'name' => 'MADEMAX SAS',
            'city' => 'YUMBO',
            'phone' => '3175527429',
        ]);

        $client = CommercialClient::query()->where('nit', '901360444-1')->first();
        $this->assertNotNull($client);
        $response->assertRedirect(route('comercial.matriz.clients.show', $client));

        $this->actingAs($user)->post(route('comercial.matriz.services.store'), [
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ2021-SF133',
            'advisor_name' => 'TATIANA',
            'doc_contract' => CommercialService::DOC_OK,
        ])->assertRedirect(route('comercial.matriz.services.index'));

        $this->actingAs($user)->post(route('comercial.matriz.services.store'), [
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_MONITOREO,
            'contract_number' => 'SJ2023-MT048',
            'advisor_name' => 'ANDREA',
        ])->assertRedirect(route('comercial.matriz.services.index'));

        $this->assertSame(2, $client->services()->count());
        $this->assertDatabaseHas('commercial_services', [
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ2021-SF133',
        ]);
        $this->assertDatabaseHas('commercial_services', [
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_MONITOREO,
            'contract_number' => 'SJ2023-MT048',
        ]);
    }

    public function test_manager_can_update_service_type_even_with_corrupt_imported_duration(): void
    {
        $user = $this->matrizManager();

        $client = CommercialClient::query()->create([
            'nit' => '10107482',
            'name' => 'CESAR AUGUSTO GOMEZ GIRALDO / PISTA BMX',
            'city' => 'MANIZALES',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $guardaType = CommercialServiceType::query()->where('name', 'GUARDA')->firstOrFail();
        $vigilanciaType = CommercialServiceType::query()->where('name', 'VIGILANCIA')->firstOrFail();

        $service = CommercialService::query()->create([
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_INACTIVOS,
            'contract_number' => 'SJ20203-SF188',
            'commercial_service_type_id' => $guardaType->id,
            'duration_months' => 7963230,
            'contract_start' => '1969-12-31',
            'contract_end' => '1969-12-31',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)->patch(route('comercial.matriz.services.update', $service), [
            'commercial_client_id' => $client->id,
            'portfolio' => $service->portfolio,
            'contract_number' => $service->contract_number,
            'commercial_service_type_id' => $vigilanciaType->id,
            'duration_months' => 7963230,
            'contract_start' => '1969-12-31',
            'contract_end' => '1969-12-31',
        ])->assertRedirect(route('comercial.matriz.services.index'));

        $service->refresh();

        $this->assertSame($vigilanciaType->id, $service->commercial_service_type_id);
        $this->assertNull($service->duration_months);
        $this->assertNull($service->contract_start);
        $this->assertNull($service->contract_end);
    }

    public function test_client_search_returns_matches_by_name_and_nit(): void
    {
        $user = $this->matrizManager();

        $client = CommercialClient::query()->create([
            'nit' => '901360444-1',
            'name' => 'MADEMAX SAS',
            'city' => 'YUMBO',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        CommercialClient::query()->create([
            'nit' => '800040390',
            'name' => 'OTRO CLIENTE',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('comercial.matriz.clients.search', ['q' => 'made']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $client->id)
            ->assertJsonCount(1, 'data');

        $this->actingAs($user)
            ->getJson(route('comercial.matriz.clients.search', ['q' => '901360']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $client->id);
    }

    public function test_service_store_requires_client(): void
    {
        $user = $this->matrizManager();

        $this->actingAs($user)
            ->post(route('comercial.matriz.services.store'), [
                'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
                'contract_number' => 'SJ-NO-CLIENT',
            ])
            ->assertSessionHasErrors(['commercial_client_id']);
    }

    public function test_inactivating_service_keeps_other_services(): void
    {
        $user = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '800040390',
            'name' => 'Cliente Demo',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $active = $client->services()->create([
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ-ACTIVE',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $keep = $client->services()->create([
            'portfolio' => CommercialService::PORTFOLIO_MONITOREO,
            'contract_number' => 'SJ-KEEP',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('comercial.matriz.services.inactivate', $active))
            ->assertRedirect(route('comercial.matriz.services.index'));

        $this->assertDatabaseHas('commercial_services', [
            'id' => $active->id,
            'portfolio' => CommercialService::PORTFOLIO_INACTIVOS,
        ]);
        $this->assertDatabaseHas('commercial_services', [
            'id' => $keep->id,
            'portfolio' => CommercialService::PORTFOLIO_MONITOREO,
        ]);
        $this->assertSame(1, $client->fresh()->activeServices()->count());
    }

    private function matrizManager(): User
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');
        $user->givePermissionTo('comercial.matriz.manage');

        return $user;
    }
}
