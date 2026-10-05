<?php

namespace Tests\Feature\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoTipoSolicitud;
use App\Models\User;
use App\Services\Access\ClienteInternoAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteInternoBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_cliente_interno_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('cliente_interno.solicitudes.view'));
        $this->assertTrue($names->contains('cliente_interno.solicitudes.edit'));
        $this->assertTrue($names->contains('cliente_interno.parameters.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.cliente_interno'));
        $this->assertFalse($names->contains('view.board.operaciones.cliente_interno'));
    }

    public function test_cliente_interno_tabs_and_board_config(): void
    {
        $tabs = config('access.cliente_interno_tabs');

        $this->assertSame('Dashboard', $tabs['dashboard']);
        $this->assertSame('Solicitudes', $tabs['solicitudes']);
        $this->assertSame('Catálogos', $tabs['catalogos']);
        $this->assertSame('Cliente interno', config('access.boards.cliente_interno'));
        $this->assertSame('gestion_humana', config('access.board_canonical_areas.cliente_interno.home'));
        $this->assertFalse(config('access.board_canonical_areas.cliente_interno.base_area_tab'));
    }

    public function test_audit_module_cliente_interno_is_configured(): void
    {
        $this->assertSame('Cliente interno', config('audit.modules.cliente_interno.label'));
        $this->assertSame('gestion_humana', config('audit.modules.cliente_interno.area'));
    }

    public function test_estado_seed_exists_and_tipos_solicitud_empty(): void
    {
        $this->assertSame(4, ClienteInternoEstado::query()->count());
        $this->assertTrue(ClienteInternoEstado::query()->where('code', 'PENDIENTE')->where('name', 'Pendiente')->exists());
        $this->assertTrue(ClienteInternoEstado::query()->where('code', 'EN_PROCESO')->where('name', 'En proceso')->exists());
        $this->assertTrue(ClienteInternoEstado::query()->where('code', 'RESPONDIDA')->where('name', 'Respondida')->exists());
        $this->assertTrue(ClienteInternoEstado::query()->where('code', 'CERRADA')->where('name', 'Cerrada')->exists());
        $this->assertSame(0, ClienteInternoTipoSolicitud::query()->count());
    }

    public function test_board_index_requires_view_or_parameters(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.index'))
            ->assertForbidden();
    }

    public function test_dashboard_requires_view_or_parameters(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.dashboard'))
            ->assertForbidden();
    }

    public function test_solicitudes_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertForbidden();
    }

    public function test_catalogos_requires_parameters_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.catalogos'))
            ->assertForbidden();
    }

    public function test_index_redirects_to_dashboard_with_view(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.index'))
            ->assertRedirect(route('gestion-humana.cliente-interno.dashboard'));
    }

    public function test_dashboard_and_solicitudes_allow_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Solicitudes', false);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertOk()
            ->assertSee('Solicitudes', false);
    }

    public function test_parameters_only_sees_dashboard_and_catalogos(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.parameters.edit',
        ]);

        $service = app(ClienteInternoAccessService::class);

        $this->assertTrue($service->canViewDashboard($user));
        $this->assertFalse($service->canViewSolicitudes($user));
        $this->assertTrue($service->canEditParameters($user));
        $this->assertSame(['dashboard', 'catalogos'], $service->visibleTabsFor($user));

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Catálogos', false)
            ->assertDontSee('>Solicitudes<', false);

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.catalogos'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertForbidden();
    }

    public function test_edit_implies_view_without_view_spatie(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.solicitudes.edit',
        ]);

        $service = app(ClienteInternoAccessService::class);

        $this->assertTrue($service->canViewSolicitudes($user));
        $this->assertTrue($service->canEditSolicitudes($user));
        $this->assertTrue($service->canViewDashboard($user));
        $this->assertSame(['dashboard', 'solicitudes'], $service->visibleTabsFor($user));

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertOk();
    }

    public function test_viewer_sees_dashboard_and_solicitudes_tabs(): void
    {
        $viewer = $this->viewerUser();
        $service = app(ClienteInternoAccessService::class);

        $this->assertSame(['dashboard', 'solicitudes'], $service->visibleTabsFor($viewer));
    }

    public function test_manage_users_bypass_can_access_board(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $service = app(ClienteInternoAccessService::class);

        $this->assertTrue($service->canViewBoard($admin));
        $this->assertTrue($service->canViewSolicitudes($admin));
        $this->assertTrue($service->canEditSolicitudes($admin));
        $this->assertTrue($service->canEditParameters($admin));
        $this->assertTrue($service->canViewDashboard($admin));
        $this->assertSame(['dashboard', 'solicitudes', 'catalogos'], $service->visibleTabsFor($admin));

        $this->actingAs($admin)
            ->get(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('gestion-humana.cliente-interno.catalogos'))
            ->assertOk();
    }

    public function test_default_cliente_interno_board_url_for_viewer(): void
    {
        $viewer = $this->viewerUser();

        $this->assertSame(
            route('gestion-humana.cliente-interno.dashboard'),
            $viewer->defaultClienteInternoBoardUrl()
        );
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.solicitudes.view',
        ]);

        return $user;
    }
}
