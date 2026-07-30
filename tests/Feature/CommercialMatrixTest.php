<?php

namespace Tests\Feature;

use App\Models\CommercialClient;
use App\Models\CommercialPortfolio;
use App\Models\CommercialSector;
use App\Models\CommercialService;
use App\Models\CommercialServiceType;
use App\Models\User;
use App\Services\Navigation\NavigationResolver;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
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

    public function test_clients_index_filters_by_active_status(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');

        $activeClient = CommercialClient::query()->create([
            'nit' => '900111222',
            'name' => 'Cliente Activo SA',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $activeClient->services()->create([
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ-ACT-1',
            'contract_end' => now()->addYear(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $inactiveClient = CommercialClient::query()->create([
            'nit' => '900333444',
            'name' => 'Cliente Inactivo SA',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $inactiveClient->services()->create([
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ-EXP-1',
            'contract_end' => now()->subDay(),
            'is_active' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('comercial.matriz.clients.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Cliente Activo SA', false)
            ->assertDontSee('Cliente Inactivo SA', false);

        $this->actingAs($user)
            ->get(route('comercial.matriz.clients.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Cliente Inactivo SA', false)
            ->assertDontSee('Cliente Activo SA', false);
    }

    public function test_client_active_when_service_expired_but_still_active_flag(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');

        $client = CommercialClient::query()->create([
            'nit' => '900333445',
            'name' => 'Cliente Vencido Activo SA',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $client->services()->create([
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ-VENC-ACT',
            'contract_end' => now()->subDay(),
            'is_active' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('comercial.matriz.clients.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Cliente Vencido Activo SA', false);

        $service = $client->services()->first();
        $this->assertSame(CommercialService::ESTADO_VENCIDO, $service->serviceEstadoLabel());
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
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('commercial_services', [
            'id' => $keep->id,
            'portfolio' => CommercialService::PORTFOLIO_MONITOREO,
        ]);
        $this->assertSame(1, $client->fresh()->activeServices()->count());
    }

    public function test_checklist_index_forbidden_without_permission(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');

        $this->actingAs($user)
            ->get(route('comercial.matriz.clients.checklist.index'))
            ->assertForbidden();
    }

    public function test_checklist_index_ok_with_view_permission(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');

        $this->actingAs($user)
            ->get(route('comercial.matriz.clients.checklist.index'))
            ->assertOk()
            ->assertSee('Checklist documental')
            ->assertSee('Gestion Clientes', false)
            ->assertSee('Clientes', false)
            ->assertSee('Servicios', false);
    }

    public function test_gestion_clientes_board_redirects_to_first_visible_tab(): void
    {
        $clientsOnly = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $clientsOnly->assignRole('usuario');
        $clientsOnly->givePermissionTo('view.board.comercial.gestion_clientes');
        $clientsOnly->givePermissionTo('view.board.comercial.matriz_clientes');

        $this->actingAs($clientsOnly)
            ->get(route('dashboard', ['module' => 'comercial', 'board' => 'gestion_clientes']))
            ->assertRedirect(route('comercial.matriz.clients.index'));

        $servicesOnly = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $servicesOnly->assignRole('usuario');
        $servicesOnly->givePermissionTo('view.board.comercial.gestion_clientes');
        $servicesOnly->givePermissionTo('view.board.comercial.servicios_comerciales');

        $this->actingAs($servicesOnly)
            ->get(route('dashboard', ['module' => 'comercial', 'board' => 'gestion_clientes']))
            ->assertRedirect(route('comercial.matriz.services.index'));
    }

    public function test_tab_subnav_respects_board_tab_permissions(): void
    {
        $clientsOnly = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $clientsOnly->assignRole('usuario');
        $clientsOnly->givePermissionTo('view.board.comercial.matriz_clientes');

        $this->actingAs($clientsOnly)
            ->get(route('comercial.matriz.clients.index'))
            ->assertOk()
            ->assertSee('class="module-tab module-tab--active"', false)
            ->assertDontSee('href="'.route('comercial.matriz.services.index').'"', false);

        $servicesOnly = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $servicesOnly->assignRole('usuario');
        $servicesOnly->givePermissionTo('view.board.comercial.servicios_comerciales');

        $this->actingAs($servicesOnly)
            ->get(route('comercial.matriz.services.index'))
            ->assertOk()
            ->assertSee('class="module-tab module-tab--active"', false)
            ->assertDontSee('href="'.route('comercial.matriz.clients.index').'"', false);
    }

    public function test_navigation_shows_single_gestion_clientes_board(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');

        $nav = app(NavigationResolver::class)->resolve($user, 'comercial.matriz.clients.index');
        $comercial = collect($nav['appNavigation'])->firstWhere('key', 'comercial');
        $boardLabels = collect($comercial['items'] ?? [])->pluck('label');

        $this->assertTrue($boardLabels->contains('Gestion Clientes'));
        $this->assertFalse($boardLabels->contains('Clientes'));
        $this->assertFalse($boardLabels->contains('Servicios'));
    }

    public function test_navigation_keeps_comercial_active_on_parameters_route(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo(['comercial.matriz.manage', 'manage.commercial.parameters']);

        $nav = app(NavigationResolver::class)->resolve($user, 'comercial.parameters.index');
        $comercial = collect($nav['appNavigation'])->firstWhere('key', 'comercial');

        $this->assertNotNull($comercial);
        $this->assertTrue($comercial['active']);
        $this->assertTrue(collect($comercial['items'])->contains('active', true));
        $this->assertTrue($nav['currentModuleTabs']->isNotEmpty());
    }

    public function test_checklist_update_requires_manage(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');

        $client = CommercialClient::query()->create([
            'nit' => '900111225',
            'name' => 'Cliente Checklist View',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('comercial.matriz.clients.checklist.update', $client), [
                'documents' => ['doc_rut' => CommercialService::DOC_OK],
            ])
            ->assertForbidden();
    }

    public function test_checklist_update_persists_status_and_client_expiry(): void
    {
        $user = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900111226',
            'name' => 'Cliente Checklist Manage',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $expires = now()->addDays(10)->toDateString();

        $this->actingAs($user)
            ->patch(route('comercial.matriz.clients.checklist.update', $client), [
                'documentation_expires_on' => $expires,
                'alert_days_before' => 15,
                'documents' => ['doc_rut' => CommercialService::DOC_OK],
            ])
            ->assertRedirect(route('comercial.matriz.clients.checklist.index'));

        $client->refresh();
        $this->assertSame($expires, $client->documentation_expires_on?->toDateString());
        $this->assertSame(15, $client->alert_days_before);

        $this->assertDatabaseHas('commercial_client_document_items', [
            'commercial_client_id' => $client->id,
            'document_key' => 'doc_rut',
            'status' => CommercialService::DOC_OK,
        ]);
    }

    public function test_service_store_does_not_require_document_fields(): void
    {
        $user = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900111223',
            'name' => 'Cliente Sin Tracking',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('comercial.matriz.services.store'), [
                'commercial_client_id' => $client->id,
                'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
                'contract_number' => 'SJ-DOC-OK',
            ])
            ->assertRedirect(route('comercial.matriz.services.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('commercial_services', [
            'commercial_client_id' => $client->id,
            'contract_number' => 'SJ-DOC-OK',
        ]);
    }

    public function test_manager_can_activate_service_after_inactivate(): void
    {
        $user = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '800040391',
            'name' => 'Cliente Reactivar',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $service = $client->services()->create([
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ-REACT',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('comercial.matriz.services.inactivate', $service))
            ->assertRedirect(route('comercial.matriz.services.index'));

        $this->assertDatabaseHas('commercial_services', [
            'id' => $service->id,
            'is_active' => false,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
        ]);

        $this->actingAs($user)
            ->post(route('comercial.matriz.services.activate', $service))
            ->assertRedirect();

        $this->assertDatabaseHas('commercial_services', [
            'id' => $service->id,
            'is_active' => true,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
        ]);
    }

    public function test_contract_vigencia_label_priority(): void
    {
        $user = $this->matrizManager();

        $inactive = CommercialService::query()->create([
            'commercial_client_id' => CommercialClient::query()->create([
                'nit' => '900111228',
                'name' => 'C1',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ])->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'is_active' => false,
            'contract_end' => now()->subDay()->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame(CommercialService::ESTADO_INACTIVO, $inactive->serviceEstadoLabel());

        $expired = CommercialService::query()->create([
            'commercial_client_id' => CommercialClient::query()->create([
                'nit' => '900111229',
                'name' => 'C2',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ])->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_end' => now()->subDay()->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame(CommercialService::ESTADO_VENCIDO, $expired->serviceEstadoLabel());

        $expiring = CommercialService::query()->create([
            'commercial_client_id' => CommercialClient::query()->create([
                'nit' => '900111230',
                'name' => 'C3',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ])->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_end' => now()->addDays(10)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame(CommercialService::ESTADO_POR_VENCER, $expiring->serviceEstadoLabel());

        $active = CommercialService::query()->create([
            'commercial_client_id' => CommercialClient::query()->create([
                'nit' => '900111231',
                'name' => 'C4',
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ])->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_end' => now()->addDays(45)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->assertSame(CommercialService::ESTADO_ACTIVO, $active->serviceEstadoLabel());
    }

    public function test_services_vigencia_filter_uses_contract_only(): void
    {
        $user = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900111227',
            'name' => 'Cliente Por Vencer',
            'documentation_expires_on' => now()->addDays(5)->toDateString(),
            'alert_days_before' => 30,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        CommercialService::query()->create([
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ-EXP-SOON',
            'contract_end' => now()->addDays(10)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        CommercialService::query()->create([
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ-DOC-ONLY',
            'contract_end' => now()->addYear()->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('comercial.matriz.services.index', ['vigencia' => 'expiring']))
            ->assertOk()
            ->assertSee('SJ-EXP-SOON')
            ->assertDontSee('SJ-DOC-ONLY');
    }

    public function test_is_expired_legacy_still_uses_documentation_for_dashboard(): void
    {
        $user = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900111224',
            'name' => 'Cliente Doc Vencido',
            'documentation_expires_on' => now()->subDay()->toDateString(),
            'alert_days_before' => 30,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $service = CommercialService::query()->create([
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ-DOC-EXPIRED',
            'contract_end' => now()->addMonths(6)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $service->load('client');

        $this->assertTrue($service->isExpired());
        $this->assertFalse($service->isExpiringSoon(30));
    }

    public function test_viewer_cannot_access_commercial_parameters(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');

        $this->actingAs($user)
            ->get(route('comercial.parameters.index'))
            ->assertForbidden();
    }

    public function test_parameters_manager_can_access_and_crud_sector(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('manage.commercial.parameters');

        $this->actingAs($user)
            ->get(route('comercial.parameters.index'))
            ->assertOk()
            ->assertSee('Tablero de Parametros', false)
            ->assertSee('Sectores', false);

        $this->actingAs($user)
            ->post(route('comercial.parameters.store', ['type' => 'sectors']), [
                'name' => 'Sector Param Test',
                'sort_order' => 99,
                'is_active' => true,
            ])
            ->assertRedirect(route('comercial.parameters.index'));

        $sector = CommercialSector::query()->where('name', 'Sector Param Test')->first();
        $this->assertNotNull($sector);
        $this->assertTrue($sector->is_active);
        $this->assertSame(99, $sector->sort_order);

        $this->actingAs($user)
            ->patch(route('comercial.parameters.update', ['type' => 'sectors', 'parameterId' => $sector->id]), [
                'name' => 'Sector Param Actualizado',
                'sort_order' => 100,
                'is_active' => false,
            ])
            ->assertRedirect(route('comercial.parameters.index'));

        $sector->refresh();
        $this->assertSame('Sector Param Actualizado', $sector->name);
        $this->assertFalse($sector->is_active);

        $this->actingAs($user)
            ->delete(route('comercial.parameters.destroy', ['type' => 'sectors', 'parameterId' => $sector->id]))
            ->assertRedirect(route('comercial.parameters.index'));

        $this->assertDatabaseMissing('commercial_sectors', ['id' => $sector->id]);
    }

    public function test_portfolios_loaded_from_database_for_service_forms_and_dashboard(): void
    {
        $portfolios = CommercialService::portfolios();

        $this->assertArrayHasKey(CommercialService::PORTFOLIO_SEG_FISICA, $portfolios);
        $this->assertArrayHasKey(CommercialService::PORTFOLIO_MONITOREO, $portfolios);
        $this->assertSame('Seg. Fisica', $portfolios[CommercialService::PORTFOLIO_SEG_FISICA]);

        CommercialPortfolio::query()
            ->where('slug', CommercialService::PORTFOLIO_SEG_FISICA)
            ->update(['name' => 'Seguridad Fisica DB']);

        $updated = CommercialService::portfolios();
        $this->assertSame('Seguridad Fisica DB', $updated[CommercialService::PORTFOLIO_SEG_FISICA]);
    }

    public function test_portfolio_store_requires_slug(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('manage.commercial.parameters');

        $this->actingAs($user)
            ->post(route('comercial.parameters.store', ['type' => 'portfolios']), [
                'name' => 'Portafolio Nuevo',
                'sort_order' => 5,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('slug');

        $this->actingAs($user)
            ->post(route('comercial.parameters.store', ['type' => 'portfolios']), [
                'slug' => 'nuevo_portafolio',
                'name' => 'Portafolio Nuevo',
                'sort_order' => 5,
                'is_active' => true,
            ])
            ->assertRedirect(route('comercial.parameters.index'));

        $this->assertDatabaseHas('commercial_portfolios', [
            'slug' => 'nuevo_portafolio',
            'name' => 'Portafolio Nuevo',
        ]);
    }

    public function test_service_store_validates_portfolio_against_database(): void
    {
        $user = $this->matrizManager();
        $client = CommercialClient::query()->create([
            'nit' => '900999888',
            'name' => 'Cliente Portfolio DB',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('comercial.matriz.services.store'), [
                'commercial_client_id' => $client->id,
                'portfolio' => 'slug_inexistente',
                'contract_number' => 'SJ-BAD-PORT',
            ])
            ->assertSessionHasErrors('portfolio');

        $this->actingAs($user)
            ->post(route('comercial.matriz.services.store'), [
                'commercial_client_id' => $client->id,
                'portfolio' => CommercialService::PORTFOLIO_MONITOREO,
                'contract_number' => 'SJ-GOOD-PORT',
            ])
            ->assertRedirect(route('comercial.matriz.services.index'))
            ->assertSessionHasNoErrors();
    }

    public function test_matriz_manager_can_access_parameters_tab(): void
    {
        $user = $this->matrizManager();

        $this->actingAs($user)
            ->get(route('comercial.parameters.index'))
            ->assertOk();
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
