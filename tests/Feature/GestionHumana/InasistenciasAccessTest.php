<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Models\WordDocumentType;
use App\Services\Access\InasistenciasAccessService;
use App\Services\Navigation\SidebarVisibilityService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InasistenciasAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_inasistencias_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('inasistencias.view'));
        $this->assertTrue($names->contains('inasistencias.edit'));
        $this->assertTrue($names->contains('view.board.gestion_humana.inasistencias'));
        $this->assertFalse($names->contains('view.board.operaciones.inasistencias'));
    }

    public function test_word_document_type_inasistencias_is_seeded(): void
    {
        $type = WordDocumentType::query()->where('code', 'inasistencias')->first();

        $this->assertNotNull($type);
        $this->assertSame('Inasistencias', $type->name);
        $this->assertTrue((bool) $type->is_active);
        $this->assertSame('inasistencias', config('employee_ficha.word_document_type_codes.inasistencias'));
        $this->assertSame(500, (int) config('inasistencias.max_rows'));
    }

    public function test_edit_implies_view_and_board_visibility(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('inasistencias.edit');

        $access = app(InasistenciasAccessService::class);
        $sidebar = app(SidebarVisibilityService::class);

        $this->assertTrue($access->canEdit($user));
        $this->assertTrue($access->canView($user));
        $this->assertTrue($access->canViewBoard($user));
        $this->assertTrue($sidebar->shouldShowBoard($user, 'gestion_humana', 'inasistencias'));
        $this->assertFalse($sidebar->shouldShowBoard($user, 'operaciones', 'inasistencias'));
        $this->assertContains('generar', $access->visibleTabsFor($user));
        $this->assertContains('historico', $access->visibleTabsFor($user));
    }

    public function test_view_only_hides_generar_tab_and_redirects(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.inasistencias',
            'inasistencias.view',
        ]);

        $access = app(InasistenciasAccessService::class);

        $this->assertTrue($access->canView($user));
        $this->assertFalse($access->canEdit($user));
        $this->assertSame(['historico'], $access->visibleTabsFor($user));

        $this->actingAs($user)
            ->get(route('gestion-humana.inasistencias.generar'))
            ->assertRedirect(route('gestion-humana.inasistencias.historico'));

        $this->actingAs($user)
            ->get(route('gestion-humana.inasistencias.historico'))
            ->assertOk()
            ->assertSee('Histórico', false);
    }

    public function test_board_only_sees_sidebar_but_pages_forbidden(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('view.board.gestion_humana.inasistencias');

        $access = app(InasistenciasAccessService::class);
        $sidebar = app(SidebarVisibilityService::class);

        $this->assertTrue($access->canViewBoard($user));
        $this->assertFalse($access->canView($user));
        $this->assertTrue($sidebar->shouldShowBoard($user, 'gestion_humana', 'inasistencias'));

        $this->actingAs($user)
            ->get(route('gestion-humana.inasistencias.historico'))
            ->assertForbidden();
    }

    public function test_edit_allows_generar_page(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.inasistencias',
            'inasistencias.edit',
        ]);

        $this->actingAs($user)
            ->get(route('gestion-humana.inasistencias.generar'))
            ->assertOk()
            ->assertSee('Generar inasistencias', false)
            ->assertSee('inasistenciasGrid', false)
            ->assertSee('Agregar fila', false);
    }

    public function test_default_board_url_prefers_generar_when_edit(): void
    {
        $editor = User::factory()->create(['must_change_password' => false]);
        $editor->givePermissionTo('inasistencias.edit');

        $this->assertSame(
            route('gestion-humana.inasistencias.generar'),
            $editor->defaultInasistenciasBoardUrl()
        );

        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo('inasistencias.view');

        $this->assertSame(
            route('gestion-humana.inasistencias.historico'),
            $viewer->defaultInasistenciasBoardUrl()
        );
    }

    public function test_administrador_does_not_receive_inasistencias_by_default(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('administrador');

        $this->assertFalse($admin->can('inasistencias.view'));
        $this->assertFalse($admin->can('inasistencias.edit'));
        $this->assertFalse($admin->can('view.board.gestion_humana.inasistencias'));
    }
}
