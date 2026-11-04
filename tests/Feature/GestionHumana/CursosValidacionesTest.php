<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CursosValidacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-15'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_validaciones_page_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cursos.validaciones'))
            ->assertForbidden();
    }

    public function test_validaciones_page_renders_for_viewer(): void
    {
        $this->actingAs($this->viewerUser())
            ->get(route('gestion-humana.cursos.validaciones'))
            ->assertOk()
            ->assertSee('Validaciones')
            ->assertSee('Activos sin curso')
            ->assertSee('Por actualizar / vencidos')
            ->assertSee('av-cola-panel__toolbar', false)
            ->assertSee('cursos-registros-page__filters av-cola-filters', false)
            ->assertSee('av-cola-panel__table', false)
            ->assertSee('cursos-validaciones-datatable-sin_curso', false)
            ->assertSee('cursos-validaciones-datatable-por_actualizar_vencidos', false)
            ->assertSee('cv_sin_curso_cargo', false)
            ->assertSee('js-cursos-validaciones-export', false)
            ->assertSee('cursos-registros-page__table-toolbar', false)
            ->assertSee('req-manage-dt-top', false);
    }

    public function test_sin_curso_datatable_lists_activos_without_cursos_only(): void
    {
        EmployeeFichaProfile::query()->create([
            'document_number' => '111',
            'full_name' => 'Sin Curso Activo',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => 'Guardia',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '222',
            'full_name' => 'Con Curso',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '333',
            'full_name' => 'Sin Curso Inactivo',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
        ]);

        $tipo = CursoTipo::factory()->create();
        EmployeeCurso::factory()->create([
            'document_number' => '222',
            'full_name' => 'Con Curso',
            'curso_tipo_id' => $tipo->id,
            'fecha_expedicion' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->viewerUser())
            ->getJson(route('gestion-humana.cursos.validaciones.datatable', [
                'cola' => 'sin_curso',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]));

        $response->assertOk()->assertJsonPath('recordsFiltered', 1);

        $rowText = collect($response->json('data'))
            ->map(fn (array $row): string => implode(' ', $row))
            ->implode(' ');

        $this->assertStringContainsString('111', $rowText);
        $this->assertStringContainsString('Sin Curso Activo', $rowText);
        $this->assertStringNotContainsString('222', $rowText);
        $this->assertStringNotContainsString('333', $rowText);
    }

    public function test_por_actualizar_vencidos_lists_one_row_per_curso_for_activos(): void
    {
        $tipo = CursoTipo::factory()->create();

        EmployeeFichaProfile::query()->create([
            'document_number' => '400',
            'full_name' => 'Activo Vencido',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '500',
            'full_name' => 'Inactivo Vencido',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
        ]);

        $vencido = EmployeeCurso::factory()->create([
            'document_number' => '400',
            'full_name' => 'Activo Vencido',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'VEN-1',
            'fecha_expedicion' => '2025-09-15',
        ]);
        $actualizar = EmployeeCurso::factory()->create([
            'document_number' => '400',
            'full_name' => 'Activo Vencido',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'ACT-1',
            'fecha_expedicion' => '2025-10-01',
        ]);
        EmployeeCurso::factory()->create([
            'document_number' => '400',
            'full_name' => 'Activo Vencido',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'VIG-1',
            'fecha_expedicion' => '2025-10-15',
        ]);
        EmployeeCurso::factory()->create([
            'document_number' => '500',
            'full_name' => 'Inactivo Vencido',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'INACT-1',
            'fecha_expedicion' => '2025-09-01',
        ]);

        $response = $this->actingAs($this->viewerUser())
            ->getJson(route('gestion-humana.cursos.validaciones.datatable', [
                'cola' => 'por_actualizar_vencidos',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]));

        $response->assertOk()->assertJsonPath('recordsFiltered', 2);

        $rowText = collect($response->json('data'))
            ->map(fn (array $row): string => implode(' ', $row))
            ->implode(' ');

        $this->assertStringContainsString('VEN-1', $rowText);
        $this->assertStringContainsString('ACT-1', $rowText);
        $this->assertStringNotContainsString('VIG-1', $rowText);
        $this->assertStringNotContainsString('INACT-1', $rowText);
        $this->assertSame(EmployeeCurso::VIGENCIA_VENCIDO, $vencido->computeVigencia());
        $this->assertSame(EmployeeCurso::VIGENCIA_ACTUALIZAR, $actualizar->computeVigencia());
    }

    public function test_sin_curso_datatable_filters_by_cargo(): void
    {
        EmployeeFichaProfile::query()->create([
            'document_number' => '811',
            'full_name' => 'Guardia Sin Curso',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => 'GUARDA',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '822',
            'full_name' => 'Escolta Sin Curso',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => 'ESCOLTA',
        ]);

        $response = $this->actingAs($this->viewerUser())
            ->getJson(route('gestion-humana.cursos.validaciones.datatable', [
                'cola' => 'sin_curso',
                'cargo' => 'GUARDA',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]));

        $response->assertOk()->assertJsonPath('recordsFiltered', 1);

        $rowText = collect($response->json('data'))
            ->map(fn (array $row): string => implode(' ', $row))
            ->implode(' ');

        $this->assertStringContainsString('811', $rowText);
        $this->assertStringContainsString('Guardia Sin Curso', $rowText);
        $this->assertStringNotContainsString('822', $rowText);
        $this->assertStringNotContainsString('Escolta Sin Curso', $rowText);
    }

    public function test_sin_curso_datatable_search_matches_name_and_cargo(): void
    {
        EmployeeFichaProfile::query()->create([
            'document_number' => '911',
            'full_name' => 'Ana Busqueda',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => 'SUPERVISOR',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '922',
            'full_name' => 'Luis Otro',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => 'GUARDA',
        ]);

        $byName = $this->actingAs($this->viewerUser())
            ->getJson(route('gestion-humana.cursos.validaciones.datatable', [
                'cola' => 'sin_curso',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'search' => ['value' => 'Ana'],
            ]));

        $byName->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->assertStringContainsString('911', implode(' ', $byName->json('data.0')));

        $byCargo = $this->actingAs($this->viewerUser())
            ->getJson(route('gestion-humana.cursos.validaciones.datatable', [
                'cola' => 'sin_curso',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'search' => ['value' => 'SUPERVISOR'],
            ]));

        $byCargo->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->assertStringContainsString('911', implode(' ', $byCargo->json('data.0')));
    }

    public function test_por_actualizar_vencidos_datatable_search(): void
    {
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'FUNDAMENTACION']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '940',
            'full_name' => 'Activo Vencido Search',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        EmployeeCurso::factory()->create([
            'document_number' => '940',
            'full_name' => 'Activo Vencido Search',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'VEN-SEARCH-1',
            'fecha_expedicion' => '2025-09-15',
        ]);
        EmployeeCurso::factory()->create([
            'document_number' => '940',
            'full_name' => 'Activo Vencido Search',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'ACT-OTRO-9',
            'fecha_expedicion' => '2025-10-01',
        ]);

        $response = $this->actingAs($this->viewerUser())
            ->getJson(route('gestion-humana.cursos.validaciones.datatable', [
                'cola' => 'por_actualizar_vencidos',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'search' => ['value' => 'VEN-SEARCH'],
            ]));

        $response->assertOk()->assertJsonPath('recordsFiltered', 1);

        $rowText = collect($response->json('data'))
            ->map(fn (array $row): string => implode(' ', $row))
            ->implode(' ');

        $this->assertStringContainsString('VEN-SEARCH-1', $rowText);
        $this->assertStringNotContainsString('ACT-OTRO-9', $rowText);
    }

    public function test_validaciones_export_sin_curso(): void
    {
        EmployeeFichaProfile::query()->create([
            'document_number' => '700',
            'full_name' => 'Export Sin Curso',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => 'GUARDA',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '701',
            'full_name' => 'Export Otro Cargo',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'position_name' => 'ESCOLTA',
        ]);

        $full = $this->actingAs($this->viewerUser())
            ->get(route('gestion-humana.cursos.validaciones.export', ['cola' => 'sin_curso']))
            ->assertOk()
            ->assertDownload();
        $this->assertStringContainsString('spreadsheetml', (string) $full->headers->get('content-type'));

        $filtered = $this->actingAs($this->viewerUser())
            ->get(route('gestion-humana.cursos.validaciones.export', [
                'cola' => 'sin_curso',
                'cargo' => 'GUARDA',
            ]))
            ->assertOk()
            ->assertDownload();
        $this->assertStringContainsString('spreadsheetml', (string) $filtered->headers->get('content-type'));
    }

    public function test_validaciones_export_por_actualizar_vencidos(): void
    {
        $tipo = CursoTipo::factory()->create();
        EmployeeFichaProfile::query()->create([
            'document_number' => '760',
            'full_name' => 'Export Vencido',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        EmployeeCurso::factory()->create([
            'document_number' => '760',
            'full_name' => 'Export Vencido',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'EXP-VEN-1',
            'fecha_expedicion' => '2025-09-15',
        ]);

        $response = $this->actingAs($this->viewerUser())
            ->get(route('gestion-humana.cursos.validaciones.export', [
                'cola' => 'por_actualizar_vencidos',
            ]))
            ->assertOk()
            ->assertDownload();

        $this->assertStringContainsString('spreadsheetml', (string) $response->headers->get('content-type'));
    }

    public function test_subnav_includes_validaciones_tab(): void
    {
        $this->actingAs($this->viewerUser())
            ->get(route('gestion-humana.cursos.registros'))
            ->assertOk()
            ->assertSee(route('gestion-humana.cursos.validaciones'), false);
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
}
