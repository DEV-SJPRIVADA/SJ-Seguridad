<?php

namespace Tests\Feature\GestionHumana;

use App\Models\User;
use App\Services\Access\ReportesNovedadesAccessService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesNovedadesBoardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_reportes_novedades_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('view.board.gestion_humana.reportes_novedades'));
        $this->assertTrue($names->contains('reportes_novedades.vacaciones.view'));
        $this->assertTrue($names->contains('reportes_novedades.vacaciones.edit'));
        $this->assertTrue($names->contains('reportes_novedades.vacaciones.review'));
        $this->assertTrue($names->contains('reportes_novedades.incapacidades.view'));
        $this->assertTrue($names->contains('reportes_novedades.retiros.edit'));
        $this->assertTrue($names->contains('reportes_novedades.permisos.review'));
        $this->assertFalse($names->contains('view.board.operaciones.reportes_novedades'));
    }

    public function test_reportes_novedades_tabs_and_board_labels(): void
    {
        $tabs = config('access.reportes_novedades_tabs');

        $this->assertSame('Vacaciones', $tabs['vacaciones']);
        $this->assertSame('Incapacidades', $tabs['incapacidades']);
        $this->assertSame('Retiros', $tabs['retiros']);
        $this->assertSame('Permisos', $tabs['permisos']);
        $this->assertSame('MT-GH-04 Novedades', config('access.boards.reportes_novedades'));
        $this->assertSame('gestion_humana', config('access.board_canonical_areas.reportes_novedades.home'));
    }

    public function test_audit_module_reportes_novedades_is_configured(): void
    {
        $this->assertSame('MT-GH-04 Novedades', config('audit.modules.reportes_novedades.label'));
        $this->assertSame('gestion_humana', config('audit.modules.reportes_novedades.area'));
    }

    public function test_index_requires_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.reportes-novedades.index'))
            ->assertForbidden();
    }

    public function test_vacaciones_requires_sheet_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertForbidden();
    }

    public function test_index_redirects_to_vacaciones_with_board_and_view(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'view.board.gestion_humana.reportes_novedades',
            'reportes_novedades.vacaciones.view',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.reportes-novedades.index'))
            ->assertRedirect(route('gestion-humana.reportes-novedades.vacaciones'));
    }

    public function test_vacaciones_shell_visible_with_board_and_view(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'view.board.gestion_humana.reportes_novedades',
            'reportes_novedades.vacaciones.view',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertOk()
            ->assertSee('MT-GH-04 Novedades', false)
            ->assertSee('Vacaciones', false)
            ->assertDontSee('Incapacidades', false)
            ->assertDontSee('Retiros', false)
            ->assertDontSee('Permisos', false);
    }

    public function test_tabs_hidden_without_sheet_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->givePermissionTo([
            'view.board.gestion_humana.reportes_novedades',
            'reportes_novedades.vacaciones.view',
            'reportes_novedades.permisos.view',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertOk()
            ->assertSee('Vacaciones', false)
            ->assertSee('Permisos', false)
            ->assertDontSee('Incapacidades', false)
            ->assertDontSee('Retiros', false);
    }

    public function test_edit_implies_view_for_sheet_access(): void
    {
        $editor = User::factory()->create(['must_change_password' => false]);
        $editor->givePermissionTo('reportes_novedades.vacaciones.edit');

        $service = app(ReportesNovedadesAccessService::class);

        $this->assertTrue($service->canView($editor, 'vacaciones'));
        $this->assertTrue($service->canEdit($editor, 'vacaciones'));
        $this->assertFalse($service->canReview($editor, 'vacaciones'));

        $this->actingAs($editor)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertOk();
    }

    public function test_review_implies_view_and_export_not_edit(): void
    {
        $reviewer = User::factory()->create(['must_change_password' => false]);
        $reviewer->givePermissionTo('reportes_novedades.vacaciones.review');

        $service = app(ReportesNovedadesAccessService::class);

        $this->assertTrue($service->canView($reviewer, 'vacaciones'));
        $this->assertTrue($service->canExport($reviewer, 'vacaciones'));
        $this->assertFalse($service->canEdit($reviewer, 'vacaciones'));
        $this->assertTrue($service->canReview($reviewer, 'vacaciones'));
    }

    public function test_manage_users_bypass_can_access_board(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->givePermissionTo('manage.users');

        $service = app(ReportesNovedadesAccessService::class);

        $this->assertTrue($service->canViewReportesNovedadesBoard($admin));
        $this->assertTrue($service->canEdit($admin, 'vacaciones'));
        $this->assertTrue($service->canReview($admin, 'retiros'));

        $this->actingAs($admin)
            ->get(route('gestion-humana.reportes-novedades.vacaciones'))
            ->assertOk()
            ->assertSee('Vacaciones', false)
            ->assertSee('Incapacidades', false)
            ->assertSee('Retiros', false)
            ->assertSee('Permisos', false);
    }

    public function test_access_service_visible_tabs_require_sheet_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $service = app(ReportesNovedadesAccessService::class);

        $this->assertSame([], $service->visibleTabsFor($user));

        $user->givePermissionTo('reportes_novedades.retiros.view');

        $this->assertSame(['retiros'], $service->visibleTabsFor($user));
    }

    public function test_fixed_catalogs_are_configured(): void
    {
        $this->assertContains('VACACIONES DISF', config('reportes_novedades.catalogos.vacaciones_novedad'));
        $this->assertContains('EG', config('reportes_novedades.catalogos.incapacidades_tipo'));
        $this->assertContains('RENUNCIA', config('reportes_novedades.catalogos.retiros_motivo'));
        $this->assertContains('LUTO', config('reportes_novedades.catalogos.permisos_novedad'));
        $this->assertSame('RETIRO', config('reportes_novedades.retiros_novedad_default'));
    }
}
