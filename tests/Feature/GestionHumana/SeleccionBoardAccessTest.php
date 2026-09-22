<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Services\Access\SeleccionAccessService;
use App\Services\Navigation\NavigationResolver;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeleccionBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_seleccion_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('seleccion.view'));
        $this->assertTrue($names->contains('seleccion.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.seleccion'));
        $this->assertFalse($names->contains('view.board.operaciones.seleccion'));
    }

    public function test_seleccion_tabs_config_labels(): void
    {
        $tabs = config('access.seleccion_tabs');

        $this->assertSame('Dashboard', $tabs['dashboard']);
        $this->assertSame('Ingreso', $tabs['ingresos']);
        $this->assertSame('Examen ocupacional', $tabs['examenes']);
        $this->assertSame('Catálogos', $tabs['catalogos']);
        $this->assertSame('Selección', config('access.boards.seleccion'));
    }

    public function test_audit_module_seleccion_is_configured(): void
    {
        $this->assertSame('Selección', config('audit.modules.seleccion.label'));
        $this->assertSame('gestion_humana', config('audit.modules.seleccion.area'));
    }

    public function test_usuario_role_does_not_receive_seleccion_permissions_by_default(): void
    {
        $usuario = Role::findByName('usuario', 'web');

        $this->assertFalse($usuario->hasPermissionTo('seleccion.view'));
        $this->assertFalse($usuario->hasPermissionTo('seleccion.edit'));
        $this->assertFalse($usuario->hasPermissionTo('view.board.gestion_humana.seleccion'));
    }

    public function test_board_index_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.seleccion.index'))
            ->assertForbidden();
    }

    public function test_ingresos_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.seleccion.ingresos'))
            ->assertForbidden();
    }

    public function test_catalogos_requires_edit_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.seleccion.catalogos'))
            ->assertForbidden();
    }

    public function test_index_redirects_to_dashboard_with_view(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.seleccion.index'))
            ->assertRedirect(route('gestion-humana.seleccion.dashboard'));
    }

    public function test_view_routes_allow_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.seleccion.dashboard'))
            ->assertOk()
            ->assertSee('Selección', false)
            ->assertSee('Dashboard', false)
            ->assertSee('Ingreso', false)
            ->assertSee('Examen ocupacional', false)
            ->assertDontSee('Catálogos', false);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.seleccion.ingresos'))
            ->assertOk();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.seleccion.examenes'))
            ->assertOk();
    }

    public function test_editor_can_access_catalogos(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.seleccion.catalogos'))
            ->assertOk()
            ->assertSee('Catálogos', false);
    }

    public function test_viewer_does_not_see_catalogos_tab(): void
    {
        $viewer = $this->viewerUser();
        $service = app(SeleccionAccessService::class);

        $this->assertSame(['dashboard', 'ingresos', 'examenes'], $service->visibleTabsFor($viewer));
        $this->assertNotContains('catalogos', $service->visibleTabsFor($viewer));
    }

    public function test_editor_sees_catalogos_tab(): void
    {
        $editor = $this->editorUser();
        $service = app(SeleccionAccessService::class);

        $this->assertSame(['dashboard', 'ingresos', 'examenes', 'catalogos'], $service->visibleTabsFor($editor));
    }

    public function test_manage_users_bypass_can_access_board(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $service = app(SeleccionAccessService::class);

        $this->assertTrue($service->canViewSeleccionBoard($admin));
        $this->assertTrue($service->canView($admin));
        $this->assertTrue($service->canEdit($admin));

        $this->actingAs($admin)
            ->get(route('gestion-humana.seleccion.ingresos'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('gestion-humana.seleccion.catalogos'))
            ->assertOk();
    }

    public function test_sidebar_shows_seleccion_label_with_board_permission(): void
    {
        $viewer = $this->viewerUser();

        $navigation = app(NavigationResolver::class)->resolve($viewer, 'dashboard', 'gestion_humana');

        $gestionHumanaModule = $navigation['appNavigation']->firstWhere('key', 'gestion_humana');
        $boardLabels = collect($gestionHumanaModule['items'] ?? [])->pluck('label');

        $this->assertTrue($boardLabels->contains('Selección'));
    }

    public function test_sidebar_hides_seleccion_without_board_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('seleccion.view');

        $navigation = app(NavigationResolver::class)->resolve($user, 'dashboard', 'gestion_humana');

        $gestionHumanaModule = $navigation['appNavigation']->firstWhere('key', 'gestion_humana');
        $boardLabels = collect($gestionHumanaModule['items'] ?? [])->pluck('label');

        $this->assertFalse($boardLabels->contains('Selección'));
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.seleccion',
            'seleccion.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.seleccion',
            'seleccion.view',
            'seleccion.edit',
        ]);

        return $user;
    }
}
