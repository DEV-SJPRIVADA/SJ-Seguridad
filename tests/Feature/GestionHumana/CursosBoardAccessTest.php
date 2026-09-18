<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\User;
use App\Services\Access\CursosAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursosBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_cursos_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('cursos.view'));
        $this->assertTrue($names->contains('cursos.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.cursos'));
        $this->assertFalse($names->contains('view.board.operaciones.cursos'));
    }

    public function test_cursos_tabs_config_labels(): void
    {
        $tabs = config('access.cursos_tabs');

        $this->assertSame('Dashboard', $tabs['dashboard']);
        $this->assertSame('Cursos', $tabs['registros']);
        $this->assertSame('Catálogo', $tabs['catalogo']);
        $this->assertSame('Cursos', config('access.boards.cursos'));
    }

    public function test_audit_module_cursos_is_configured(): void
    {
        $this->assertSame('Cursos', config('audit.modules.cursos.label'));
        $this->assertSame('gestion_humana', config('audit.modules.cursos.area'));
    }

    public function test_board_index_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cursos.index'))
            ->assertForbidden();
    }

    public function test_registros_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cursos.registros'))
            ->assertForbidden();
    }

    public function test_catalogo_requires_edit_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.catalogo'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cursos.catalogo.store'), [
                'tipo_curso' => 'ALTURAS',
                'is_active' => '1',
            ])
            ->assertForbidden();
    }

    public function test_index_redirects_to_dashboard_with_view(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.index'))
            ->assertRedirect(route('gestion-humana.cursos.dashboard'));
    }

    public function test_registros_allows_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros'))
            ->assertOk()
            ->assertSee('Cursos', false)
            ->assertDontSee('Catálogo', false);
    }

    public function test_viewer_does_not_see_catalogo_tab(): void
    {
        $viewer = $this->viewerUser();
        $service = app(CursosAccessService::class);

        $this->assertSame(['dashboard', 'registros'], $service->visibleTabsFor($viewer));
        $this->assertNotContains('catalogo', $service->visibleTabsFor($viewer));
    }

    public function test_editor_sees_catalogo_tab(): void
    {
        $editor = $this->editorUser();
        $service = app(CursosAccessService::class);

        $this->assertSame(['dashboard', 'registros', 'catalogo'], $service->visibleTabsFor($editor));
    }

    public function test_manage_users_bypass_can_access_board(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $service = app(CursosAccessService::class);

        $this->assertTrue($service->canViewCursosBoard($admin));
        $this->assertTrue($service->canView($admin));
        $this->assertTrue($service->canEdit($admin));

        $this->actingAs($admin)
            ->get(route('gestion-humana.cursos.registros'))
            ->assertOk();
    }

    public function test_editor_can_store_curso_tipo(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.catalogo.store'), [
                'tipo_curso' => 'ALTURAS',
                'cargo_curso' => 'Vigilante',
                'formato_para_cursos' => 'PRESENCIAL',
                'cursos' => 'Trabajo en alturas',
                'cargo_acredit' => 'Acreditado',
                'is_active' => '1',
            ])
            ->assertRedirect(route('gestion-humana.cursos.catalogo', ['catalog' => 'tipos']));

        $this->assertDatabaseHas('curso_tipos', [
            'tipo_curso' => 'ALTURAS',
            'cargo_curso' => 'Vigilante',
            'formato_para_cursos' => 'PRESENCIAL',
            'cursos' => 'Trabajo en alturas',
            'cargo_acredit' => 'Acreditado',
            'is_active' => 1,
        ]);
    }

    public function test_cannot_destroy_curso_tipo_with_employee_cursos(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'PRIMEROS AUXILIOS']);

        EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.cursos.catalogo.destroy', $tipo))
            ->assertRedirect(route('gestion-humana.cursos.catalogo', ['catalog' => 'tipos']))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('curso_tipos', [
            'id' => $tipo->id,
            'tipo_curso' => 'PRIMEROS AUXILIOS',
        ]);
    }

    public function test_can_destroy_curso_tipo_without_children(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'VACIO']);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.cursos.catalogo.destroy', $tipo))
            ->assertRedirect(route('gestion-humana.cursos.catalogo', ['catalog' => 'tipos']))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('curso_tipos', ['id' => $tipo->id]);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cursos',
            'cursos.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cursos',
            'cursos.view',
            'cursos.edit',
        ]);

        return $user;
    }
}
