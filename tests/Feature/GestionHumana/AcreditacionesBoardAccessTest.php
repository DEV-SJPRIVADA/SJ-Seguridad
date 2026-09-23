<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Services\Access\AcreditacionesAccessService;
use App\Services\Navigation\NavigationResolver;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcreditacionesBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_acreditaciones_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('acreditaciones.view'));
        $this->assertTrue($names->contains('acreditaciones.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.acreditaciones'));
        $this->assertFalse($names->contains('view.board.operaciones.acreditaciones'));
    }

    public function test_acreditaciones_tabs_config_labels(): void
    {
        $tabs = config('access.acreditaciones_tabs');

        $this->assertSame('Dashboard', $tabs['dashboard']);
        $this->assertSame('Acreditados', $tabs['acreditados']);
        $this->assertSame('Reporte Diario', $tabs['reporte_diario']);
        $this->assertSame('Validaciones', $tabs['validaciones']);
        $this->assertSame('Export Apo', $tabs['export_apo']);
        $this->assertSame('Catálogo', $tabs['catalogo']);
        $this->assertSame('Acreditaciones', config('access.boards.acreditaciones'));
    }

    public function test_audit_module_acreditaciones_is_configured(): void
    {
        $this->assertSame('Acreditaciones', config('audit.modules.acreditaciones.label'));
        $this->assertSame('gestion_humana', config('audit.modules.acreditaciones.area'));
    }

    public function test_usuario_role_does_not_receive_acreditaciones_permissions_by_default(): void
    {
        $usuario = Role::findByName('usuario', 'web');
        $administrador = Role::findByName('administrador', 'web');

        $this->assertFalse($usuario->hasPermissionTo('acreditaciones.view'));
        $this->assertFalse($usuario->hasPermissionTo('acreditaciones.edit'));
        $this->assertFalse($usuario->hasPermissionTo('view.board.gestion_humana.acreditaciones'));

        $this->assertFalse($administrador->hasPermissionTo('acreditaciones.view'));
        $this->assertFalse($administrador->hasPermissionTo('acreditaciones.edit'));
        $this->assertFalse($administrador->hasPermissionTo('view.board.gestion_humana.acreditaciones'));
    }

    public function test_board_index_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.acreditaciones.index'))
            ->assertForbidden();
    }

    public function test_acreditados_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.acreditaciones.acreditados'))
            ->assertForbidden();
    }

    public function test_catalogo_requires_edit_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.catalogo'))
            ->assertForbidden();
    }

    public function test_index_redirects_to_acreditados_with_view(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.index'))
            ->assertRedirect(route('gestion-humana.acreditaciones.acreditados'));
    }

    public function test_view_routes_allow_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.dashboard'))
            ->assertOk()
            ->assertSee('Acreditaciones', false)
            ->assertSee('Dashboard', false)
            ->assertSee('Acreditados', false)
            ->assertSee('Reporte Diario', false)
            ->assertSee('Validaciones', false)
            ->assertSee('Export Apo', false)
            ->assertDontSee('Catálogo', false)
            ->assertSee('Próximamente', false);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.acreditados'))
            ->assertOk()
            ->assertSee('Acreditados', false)
            ->assertSee('VIGEN.ACR', false)
            ->assertDontSee('Próximamente', false);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.reporte-diario'))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.validaciones'))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.export-apo'))
            ->assertOk();
    }

    public function test_editor_can_access_catalogo(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.catalogo'))
            ->assertOk()
            ->assertSee('Catálogo', false)
            ->assertSee('Catálogo de cargos', false);
    }

    public function test_viewer_does_not_see_catalogo_tab(): void
    {
        $viewer = $this->viewerUser();
        $service = app(AcreditacionesAccessService::class);

        $this->assertSame(
            ['dashboard', 'acreditados', 'reporte_diario', 'validaciones', 'export_apo'],
            $service->visibleTabsFor($viewer)
        );
        $this->assertNotContains('catalogo', $service->visibleTabsFor($viewer));
    }

    public function test_editor_sees_catalogo_tab(): void
    {
        $editor = $this->editorUser();
        $service = app(AcreditacionesAccessService::class);

        $this->assertSame(
            ['dashboard', 'acreditados', 'reporte_diario', 'validaciones', 'export_apo', 'catalogo'],
            $service->visibleTabsFor($editor)
        );
    }

    public function test_manage_users_bypass_can_access_board(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $service = app(AcreditacionesAccessService::class);

        $this->assertTrue($service->canViewAcreditacionesBoard($admin));
        $this->assertTrue($service->canView($admin));
        $this->assertTrue($service->canEdit($admin));

        $this->actingAs($admin)
            ->get(route('gestion-humana.acreditaciones.acreditados'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('gestion-humana.acreditaciones.catalogo'))
            ->assertOk();
    }

    public function test_sidebar_shows_acreditaciones_label_with_board_permission(): void
    {
        $viewer = $this->viewerUser();

        $navigation = app(NavigationResolver::class)->resolve($viewer, 'dashboard', 'gestion_humana');

        $gestionHumanaModule = $navigation['appNavigation']->firstWhere('key', 'gestion_humana');
        $boardLabels = collect($gestionHumanaModule['items'] ?? [])->pluck('label');

        $this->assertTrue($boardLabels->contains('Acreditaciones'));
    }

    public function test_sidebar_hides_acreditaciones_without_board_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('acreditaciones.view');

        $navigation = app(NavigationResolver::class)->resolve($user, 'dashboard', 'gestion_humana');

        $gestionHumanaModule = $navigation['appNavigation']->firstWhere('key', 'gestion_humana');
        $boardLabels = collect($gestionHumanaModule['items'] ?? [])->pluck('label');

        $this->assertFalse($boardLabels->contains('Acreditaciones'));
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.acreditaciones',
            'acreditaciones.view',
            'acreditaciones.edit',
        ]);

        return $user;
    }
}
