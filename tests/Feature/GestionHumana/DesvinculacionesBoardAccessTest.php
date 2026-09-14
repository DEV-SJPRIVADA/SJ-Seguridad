<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Services\Access\DesvinculacionesAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesvinculacionesBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_desvinculaciones_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('desvinculaciones.view'));
        $this->assertTrue($names->contains('desvinculaciones.masivos'));
        $this->assertTrue($names->contains('desvinculaciones.seguimientos.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.desvinculaciones'));
        $this->assertFalse($names->contains('view.board.operaciones.desvinculaciones'));
    }

    public function test_desvinculaciones_tabs_config_labels(): void
    {
        $tabs = config('access.desvinculaciones_tabs');

        $this->assertSame('Masivos', $tabs['masivos']);
        $this->assertSame('Seguimientos', $tabs['seguimientos']);
        $this->assertSame('Desvinculaciones', config('access.boards.desvinculaciones'));
    }

    public function test_desvinculaciones_index_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.desvinculaciones.index'))
            ->assertForbidden();
    }

    public function test_desvinculaciones_masivos_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.desvinculaciones.masivos'))
            ->assertForbidden();
    }

    public function test_desvinculaciones_seguimientos_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.desvinculaciones.seguimientos'))
            ->assertForbidden();
    }

    public function test_desvinculaciones_index_redirects_to_masivos_with_view(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'desvinculaciones.view',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.desvinculaciones.index'))
            ->assertRedirect(route('gestion-humana.desvinculaciones.masivos'));
    }

    public function test_desvinculaciones_masivos_allows_view_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'desvinculaciones.view',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.desvinculaciones.masivos'))
            ->assertOk()
            ->assertSee('Desvinculaciones', false)
            ->assertSee('Masivos', false)
            ->assertSee('Seguimientos', false);
    }

    public function test_desvinculaciones_seguimientos_allows_view_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'desvinculaciones.view',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.desvinculaciones.seguimientos'))
            ->assertOk()
            ->assertSee('Seguimientos', false);
    }

    public function test_manage_users_bypass_can_access_board(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $service = app(DesvinculacionesAccessService::class);

        $this->assertTrue($service->canViewDesvinculacionesBoard($admin));
        $this->assertTrue($service->canView($admin));
        $this->assertTrue($service->canMasivos($admin));
        $this->assertTrue($service->canEditSeguimientos($admin));

        $this->actingAs($admin)
            ->get(route('gestion-humana.desvinculaciones.masivos'))
            ->assertOk();
    }

    public function test_access_service_visible_tabs_require_view(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $service = app(DesvinculacionesAccessService::class);

        $this->assertSame([], $service->visibleTabsFor($user));

        $user->givePermissionTo('desvinculaciones.view');

        $this->assertSame(['masivos', 'seguimientos'], $service->visibleTabsFor($user));
    }

    public function test_audit_module_desvinculaciones_is_configured(): void
    {
        $this->assertSame('Desvinculaciones', config('audit.modules.desvinculaciones.label'));
        $this->assertSame('gestion_humana', config('audit.modules.desvinculaciones.area'));
    }
}
