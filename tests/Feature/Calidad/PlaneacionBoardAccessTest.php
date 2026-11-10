<?php

namespace Tests\Feature\Calidad;

use App\Models\User;
use App\Services\Access\PlaneacionEstrategicaAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaneacionBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_permissions_exist_only_for_calidad(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('view.board.calidad.planeacion_estrategica'));
        $this->assertTrue($names->contains('planeacion.dashboard.view'));
        $this->assertTrue($names->contains('planeacion.indicadores.view'));
        $this->assertTrue($names->contains('planeacion.indicadores.edit'));
        $this->assertTrue($names->contains('planeacion.captura.view'));
        $this->assertTrue($names->contains('planeacion.captura.edit'));
        $this->assertFalse($names->contains('view.board.gestion_humana.planeacion_estrategica'));
        $this->assertFalse($names->contains('view.board.operaciones.planeacion_estrategica'));
        $this->assertSame('Planeación estratégica', config('access.boards.planeacion_estrategica'));
        $this->assertSame('calidad', config('access.board_canonical_areas.planeacion_estrategica.home'));
        $this->assertFalse(config('access.board_canonical_areas.planeacion_estrategica.base_area_tab'));
        $this->assertSame('Planeación estratégica', config('audit.modules.planeacion_estrategica.label'));
    }

    public function test_guest_routes_are_forbidden_without_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)->get(route('calidad.planeacion.index'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.planeacion.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.planeacion.indicadores'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.planeacion.indicadores.create'))->assertForbidden();
        $this->actingAs($user)->get(route('calidad.planeacion.captura'))->assertForbidden();
    }

    public function test_viewer_lists_indicators_but_cannot_create(): void
    {
        $viewer = $this->userWith([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.indicadores.view',
        ]);

        $this->assertSame(['indicadores'], app(PlaneacionEstrategicaAccessService::class)->visibleTabsFor($viewer));
        $this->assertSame(route('calidad.planeacion.indicadores'), $viewer->defaultPlaneacionBoardUrl());

        $this->actingAs($viewer)->get(route('calidad.planeacion.indicadores'))->assertOk();
        $this->actingAs($viewer)->get(route('calidad.planeacion.indicadores.create'))->assertForbidden();
        $this->actingAs($viewer)->get(route('calidad.planeacion.dashboard'))->assertForbidden();
        $this->actingAs($viewer)->get(route('calidad.planeacion.captura'))->assertForbidden();
    }

    public function test_administrador_role_receives_the_package(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('administrador');

        $this->assertTrue($admin->can('view.board.calidad.planeacion_estrategica'));
        $this->assertTrue($admin->can('planeacion.indicadores.edit'));
        $this->assertTrue($admin->can('planeacion.dashboard.view'));
        $this->assertTrue($admin->can('planeacion.captura.view'));
        $this->assertTrue($admin->can('planeacion.captura.edit'));
    }

    public function test_edit_implies_view_tabs_but_not_dashboard(): void
    {
        $editor = $this->userWith([
            'view.board.calidad.planeacion_estrategica',
            'planeacion.indicadores.edit',
        ]);

        $access = app(PlaneacionEstrategicaAccessService::class);

        $this->assertTrue($access->canEditIndicadores($editor));
        $this->assertTrue($access->canViewIndicadores($editor));
        $this->assertFalse($access->canViewDashboard($editor));
        $this->assertFalse($access->canViewCapture($editor));
        $this->assertSame(['indicadores'], $access->visibleTabsFor($editor));

        $this->actingAs($editor)->get(route('calidad.planeacion.indicadores'))->assertOk();
        $this->actingAs($editor)->get(route('calidad.planeacion.indicadores.create'))->assertOk();
        $this->actingAs($editor)->get(route('calidad.planeacion.dashboard'))->assertForbidden();
        $this->actingAs($editor)->get(route('calidad.planeacion.captura'))->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo($permissions);

        return $user;
    }
}
