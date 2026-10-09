<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Services\Access\MtSt04AccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MtSt04BoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_mt_st_04_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('mt_st_04.view'));
        $this->assertTrue($names->contains('mt_st_04.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.mt_st_04'));
        $this->assertFalse($names->contains('view.board.operaciones.mt_st_04'));
        $this->assertFalse($names->contains('mt_st_04.parameters.edit'));
    }

    public function test_mt_st_04_tabs_config_labels(): void
    {
        $tabs = config('access.mt_st_04_tabs');

        $this->assertSame('Dashboard', $tabs['dashboard']);
        $this->assertSame('Matriz', $tabs['matriz']);
        $this->assertSame('Validaciones', $tabs['validaciones']);
        $this->assertSame('MT-ST-04', config('access.boards.mt_st_04'));
        $this->assertSame('gestion_humana', config('access.board_canonical_areas.mt_st_04.home'));
    }

    public function test_audit_module_mt_st_04_is_configured(): void
    {
        $this->assertSame('MT-ST-04', config('audit.modules.mt_st_04.label'));
        $this->assertSame('gestion_humana', config('audit.modules.mt_st_04.area'));
    }

    public function test_mt_st_04_config_windows_and_cargos(): void
    {
        $this->assertSame(364, config('mt_st_04.vencimiento_dias'));
        $this->assertSame(30, config('mt_st_04.vencera_dias'));
        $this->assertSame(['GUARDA', 'OPERADOR'], config('mt_st_04.no_aplica_cargos'));
        $this->assertSame('America/Bogota', config('mt_st_04.timezone'));
    }

    public function test_board_index_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.mt-st-04.index'))
            ->assertForbidden();
    }

    public function test_dashboard_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.mt-st-04.dashboard'))
            ->assertForbidden();
    }

    public function test_matriz_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.mt-st-04.matriz'))
            ->assertForbidden();
    }

    public function test_index_redirects_to_dashboard_with_view(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.index'))
            ->assertRedirect(route('gestion-humana.mt-st-04.dashboard'));
    }

    public function test_dashboard_allows_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Matriz', false);
    }

    public function test_matriz_allows_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.matriz'))
            ->assertOk()
            ->assertSee('Matriz', false);
    }

    public function test_edit_implies_view_without_view_spatie(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.mt_st_04',
            'mt_st_04.edit',
        ]);

        $service = app(MtSt04AccessService::class);

        $this->assertTrue($service->canView($user));
        $this->assertTrue($service->canEdit($user));
        $this->assertSame(['dashboard', 'matriz'], $service->visibleTabsFor($user));

        $this->actingAs($user)
            ->get(route('gestion-humana.mt-st-04.dashboard'))
            ->assertOk();
    }

    public function test_viewer_sees_both_tabs(): void
    {
        $viewer = $this->viewerUser();
        $service = app(MtSt04AccessService::class);

        $this->assertSame(['dashboard', 'matriz'], $service->visibleTabsFor($viewer));
    }

    public function test_manage_users_bypass_can_access_board(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $service = app(MtSt04AccessService::class);

        $this->assertTrue($service->canViewBoard($admin));
        $this->assertTrue($service->canView($admin));
        $this->assertTrue($service->canEdit($admin));

        $this->actingAs($admin)
            ->get(route('gestion-humana.mt-st-04.matriz'))
            ->assertOk();
    }

    public function test_default_mt_st_04_board_url_for_viewer(): void
    {
        $viewer = $this->viewerUser();

        $this->assertSame(
            route('gestion-humana.mt-st-04.dashboard'),
            $viewer->defaultMtSt04BoardUrl()
        );
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.mt_st_04',
            'mt_st_04.view',
        ]);

        return $user;
    }
}
