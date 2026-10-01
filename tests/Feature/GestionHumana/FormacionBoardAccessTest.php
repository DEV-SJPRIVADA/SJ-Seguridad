<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Services\Access\FormacionAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormacionBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_formacion_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('formacion.view'));
        $this->assertTrue($names->contains('formacion.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.formacion'));
        $this->assertFalse($names->contains('view.board.operaciones.formacion'));
    }

    public function test_formacion_tabs_config_labels(): void
    {
        $tabs = config('access.formacion_tabs');

        $this->assertSame('Dashboard', $tabs['dashboard']);
        $this->assertSame('Formaciones', $tabs['formaciones']);
        $this->assertSame('Formación', config('access.boards.formacion'));
        $this->assertSame('gestion_humana', config('access.board_canonical_areas.formacion.home'));
    }

    public function test_audit_module_formacion_is_configured(): void
    {
        $this->assertSame('Formación', config('audit.modules.formacion.label'));
        $this->assertSame('gestion_humana', config('audit.modules.formacion.area'));
    }

    public function test_board_index_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.formacion.index'))
            ->assertForbidden();
    }

    public function test_dashboard_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.formacion.dashboard'))
            ->assertForbidden();
    }

    public function test_formaciones_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.formacion.formaciones'))
            ->assertForbidden();
    }

    public function test_index_redirects_to_dashboard_with_view(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.index'))
            ->assertRedirect(route('gestion-humana.formacion.dashboard'));
    }

    public function test_dashboard_allows_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Formaciones', false);
    }

    public function test_formaciones_allows_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.formaciones'))
            ->assertOk()
            ->assertSee('Formaciones', false);
    }

    public function test_edit_implies_view_without_view_spatie(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.formacion',
            'formacion.edit',
        ]);

        $service = app(FormacionAccessService::class);

        $this->assertTrue($service->canView($user));
        $this->assertTrue($service->canEdit($user));
        $this->assertSame(['dashboard', 'formaciones'], $service->visibleTabsFor($user));

        $this->actingAs($user)
            ->get(route('gestion-humana.formacion.dashboard'))
            ->assertOk();
    }

    public function test_viewer_sees_both_tabs(): void
    {
        $viewer = $this->viewerUser();
        $service = app(FormacionAccessService::class);

        $this->assertSame(['dashboard', 'formaciones'], $service->visibleTabsFor($viewer));
    }

    public function test_manage_users_bypass_can_access_board(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $service = app(FormacionAccessService::class);

        $this->assertTrue($service->canViewBoard($admin));
        $this->assertTrue($service->canView($admin));
        $this->assertTrue($service->canEdit($admin));

        $this->actingAs($admin)
            ->get(route('gestion-humana.formacion.formaciones'))
            ->assertOk();
    }

    public function test_default_formacion_board_url_for_viewer(): void
    {
        $viewer = $this->viewerUser();

        $this->assertSame(
            route('gestion-humana.formacion.dashboard'),
            $viewer->defaultFormacionBoardUrl()
        );
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.formacion',
            'formacion.view',
        ]);

        return $user;
    }
}
