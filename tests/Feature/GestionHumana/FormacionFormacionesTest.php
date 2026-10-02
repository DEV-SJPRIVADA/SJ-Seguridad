<?php

namespace Tests\Feature\GestionHumana;

use App\Models\FormacionRegistro;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormacionFormacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_formaciones_page_includes_datatable_filters_and_export(): void
    {
        FormacionRegistro::factory()->create([
            'anio' => 2026,
            'mes' => 6,
            'categoria' => 'Obligatoria',
            'nombre_curso' => 'Inducción SST',
        ]);

        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.formaciones', [
                'anio' => '2026',
                'categoria' => 'Obligatoria',
            ]))
            ->assertOk()
            ->assertViewHas('datatableUrl')
            ->assertViewHas('exportUrl')
            ->assertSee('js-formacion-formaciones-datatable', false)
            ->assertSee('>Estado</th>', false)
            ->assertSee('filter_estado', false)
            ->assertSee('anio=2026', false)
            ->assertSee('categoria=Obligatoria', false)
            ->assertSee(route('gestion-humana.formacion.formaciones.export'), false)
            ->assertSee('serverSide: true', false);
    }

    public function test_datatable_responds_with_rows(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => '100200300',
            'nombre_completo' => 'Ana Formacion',
            'nombre_curso' => 'Primeros auxilios',
            'categoria' => 'Complementaria',
            'fecha_inicio' => '2026-03-15',
            'mes' => 3,
            'anio' => 2026,
            'calificacion' => null,
        ]);

        $viewer = $this->viewerUser();

        $response = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $rowText = collect($response->json('data'))
            ->map(fn (array $row): string => implode(' ', $row))
            ->implode(' ');

        $this->assertStringContainsString('100200300', $rowText);
        $this->assertStringContainsString('Ana Formacion', $rowText);
        $this->assertStringContainsString('Primeros auxilios', $rowText);
        $this->assertStringContainsString('No realizada', $rowText);
    }

    public function test_datatable_estado_from_calificacion(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => 'E1',
            'nombre_completo' => 'Aprobado Uno',
            'calificacion' => '8',
            'nombre_curso' => 'Curso A',
            'categoria' => 'Obligatoria',
            'anio' => 2026,
            'mes' => 1,
            'fecha_inicio' => '2026-01-01',
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => 'E2',
            'nombre_completo' => 'Reprobado Dos',
            'calificacion' => '7.5',
            'nombre_curso' => 'Curso B',
            'categoria' => 'Obligatoria',
            'anio' => 2026,
            'mes' => 1,
            'fecha_inicio' => '2026-01-02',
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => 'E3',
            'nombre_completo' => 'Vacio Tres',
            'calificacion' => null,
            'nombre_curso' => 'Curso C',
            'categoria' => 'Obligatoria',
            'anio' => 2026,
            'mes' => 1,
            'fecha_inicio' => '2026-01-03',
        ]);

        $viewer = $this->viewerUser();

        $response = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk();

        $byId = [];
        foreach ($response->json('data') as $row) {
            $byId[$row[0]] = $row;
        }

        $this->assertStringContainsString('status-pill--success', $byId['E1'][7]);
        $this->assertStringContainsString('Aprobado', strip_tags($byId['E1'][7]));
        $this->assertStringContainsString('status-pill--danger', $byId['E2'][7]);
        $this->assertStringContainsString('Reprobado', strip_tags($byId['E2'][7]));
        $this->assertStringContainsString('status-pill--muted', $byId['E3'][7]);
        $this->assertStringContainsString('No realizada', strip_tags($byId['E3'][7]));
    }

    public function test_datatable_filters_by_estado(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => 'A1',
            'nombre_completo' => 'Aprobado Filtro',
            'calificacion' => '9',
            'nombre_curso' => 'Curso',
            'categoria' => 'Obligatoria',
            'anio' => 2026,
            'mes' => 2,
            'fecha_inicio' => '2026-02-01',
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => 'R1',
            'nombre_completo' => 'Reprobado Filtro',
            'calificacion' => '6',
            'nombre_curso' => 'Curso',
            'categoria' => 'Obligatoria',
            'anio' => 2026,
            'mes' => 2,
            'fecha_inicio' => '2026-02-02',
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => 'N1',
            'nombre_completo' => 'No Realizada Filtro',
            'calificacion' => null,
            'nombre_curso' => 'Curso',
            'categoria' => 'Obligatoria',
            'anio' => 2026,
            'mes' => 2,
            'fecha_inicio' => '2026-02-03',
        ]);

        $viewer = $this->viewerUser();

        $aprobado = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'estado' => 'aprobado',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk();

        $this->assertSame(1, $aprobado->json('recordsFiltered'));
        $this->assertStringContainsString('A1', $this->datatableRowText($aprobado->json('data')));

        $reprobado = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'estado' => 'reprobado',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk();

        $this->assertSame(1, $reprobado->json('recordsFiltered'));
        $this->assertStringContainsString('R1', $this->datatableRowText($reprobado->json('data')));

        $noRealizada = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'estado' => 'no_realizada',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk();

        $this->assertSame(1, $noRealizada->json('recordsFiltered'));
        $this->assertStringContainsString('N1', $this->datatableRowText($noRealizada->json('data')));
    }

    public function test_datatable_applies_filters(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => '111',
            'nombre_completo' => 'Match Filtro',
            'nombre_curso' => 'Trabajo en alturas',
            'categoria' => 'Obligatoria',
            'fecha_inicio' => '2025-06-04',
            'mes' => 6,
            'anio' => 2025,
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => '222',
            'nombre_completo' => 'Otro Registro',
            'nombre_curso' => 'Manejo defensivo',
            'categoria' => 'Complementaria',
            'fecha_inicio' => '2026-01-10',
            'mes' => 1,
            'anio' => 2026,
        ]);

        $viewer = $this->viewerUser();

        $byAnio = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'anio' => 2025,
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk();

        $this->assertSame(1, $byAnio->json('recordsFiltered'));
        $this->assertStringContainsString('Match Filtro', $this->datatableRowText($byAnio->json('data')));
        $this->assertStringNotContainsString('Otro Registro', $this->datatableRowText($byAnio->json('data')));

        $byMesCategoria = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'mes' => 6,
                'categoria' => 'Obligatoria',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk();

        $this->assertSame(1, $byMesCategoria->json('recordsFiltered'));

        $byCursoNombre = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'nombre_curso' => 'Manejo defensivo',
                'nombre' => 'Otro',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk();

        $this->assertSame(1, $byCursoNombre->json('recordsFiltered'));
        $this->assertStringContainsString('222', $this->datatableRowText($byCursoNombre->json('data')));

        $byNumeroId = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'numero_id' => '111',
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk();

        $this->assertSame(1, $byNumeroId->json('recordsFiltered'));
        $this->assertStringContainsString('Match Filtro', $this->datatableRowText($byNumeroId->json('data')));
    }

    public function test_options_returns_distinct_filter_values(): void
    {
        FormacionRegistro::factory()->create([
            'anio' => 2024,
            'categoria' => 'Obligatoria',
            'nombre_curso' => 'Curso A',
        ]);
        FormacionRegistro::factory()->create([
            'anio' => 2026,
            'categoria' => 'Complementaria',
            'nombre_curso' => 'Curso B',
        ]);

        $viewer = $this->viewerUser();

        $response = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.options'))
            ->assertOk()
            ->assertJsonStructure([
                'anios',
                'meses',
                'categorias',
                'cursos',
            ]);

        $anios = collect($response->json('anios'))->pluck('value')->all();
        $categorias = collect($response->json('categorias'))->pluck('value')->all();
        $cursos = collect($response->json('cursos'))->pluck('value')->all();

        $this->assertContains('2024', $anios);
        $this->assertContains('2026', $anios);
        $this->assertContains('Obligatoria', $categorias);
        $this->assertContains('Complementaria', $categorias);
        $this->assertContains('Curso A', $cursos);
        $this->assertContains('Curso B', $cursos);
        $this->assertCount(12, $response->json('meses'));
    }

    public function test_options_cursos_depend_on_anio_and_mes(): void
    {
        FormacionRegistro::factory()->create([
            'anio' => 2026,
            'mes' => 1,
            'nombre_curso' => 'Solo Enero',
            'categoria' => 'A',
            'fecha_inicio' => '2026-01-01',
        ]);
        FormacionRegistro::factory()->create([
            'anio' => 2026,
            'mes' => 2,
            'nombre_curso' => 'Solo Febrero',
            'categoria' => 'A',
            'fecha_inicio' => '2026-02-01',
        ]);
        FormacionRegistro::factory()->create([
            'anio' => 2025,
            'mes' => 2,
            'nombre_curso' => 'Otro Año',
            'categoria' => 'A',
            'fecha_inicio' => '2025-02-01',
        ]);

        $viewer = $this->viewerUser();

        $byAnio = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.options', [
                'anio' => 2026,
            ]))
            ->assertOk()
            ->json('cursos');

        $byAnioLabels = collect($byAnio)->pluck('value')->all();
        $this->assertContains('Solo Enero', $byAnioLabels);
        $this->assertContains('Solo Febrero', $byAnioLabels);
        $this->assertNotContains('Otro Año', $byAnioLabels);

        $byMes = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.options', [
                'anio' => 2026,
                'mes' => 2,
            ]))
            ->assertOk()
            ->json('cursos');

        $byMesLabels = collect($byMes)->pluck('value')->all();
        $this->assertContains('Solo Febrero', $byMesLabels);
        $this->assertNotContains('Solo Enero', $byMesLabels);
        $this->assertNotContains('Otro Año', $byMesLabels);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.formaciones', [
                'anio' => 2026,
                'mes' => 2,
                'nombre_curso' => 'Solo Enero',
            ]))
            ->assertOk()
            ->assertSee('filter_nombre_curso', false)
            ->assertDontSee('value="Solo Enero"', false);
    }

    public function test_datatable_filters_by_ciclo_person_ids(): void
    {
        $year = 2026;

        FormacionRegistro::factory()->create([
            'numero_id' => 'C1',
            'nombre_completo' => 'Ciclo Aprobado',
            'anio' => $year,
            'mes' => 5,
            'nombre_curso' => 'Altura',
            'calificacion' => '9',
            'fecha_inicio' => '2026-05-01',
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => 'C1',
            'nombre_completo' => 'Ciclo Aprobado',
            'anio' => $year,
            'mes' => 5,
            'nombre_curso' => 'Defensivo',
            'calificacion' => '8',
            'fecha_inicio' => '2026-05-02',
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => 'C2',
            'nombre_completo' => 'Ciclo Incompleto',
            'anio' => $year,
            'mes' => 5,
            'nombre_curso' => 'Altura',
            'calificacion' => '9',
            'fecha_inicio' => '2026-05-03',
        ]);

        $viewer = $this->viewerUser();

        $aprobados = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'anio' => $year,
                'mes' => 5,
                'ciclo' => 'aprobado',
            ]))
            ->assertOk();

        $this->assertSame(2, $aprobados->json('recordsFiltered'));
        $aprobadosText = $this->datatableRowText($aprobados->json('data'));
        $this->assertStringContainsString('Ciclo Aprobado', $aprobadosText);
        $this->assertStringNotContainsString('Ciclo Incompleto', $aprobadosText);

        $incompletos = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'anio' => $year,
                'mes' => 5,
                'ciclo' => 'incompleto',
            ]))
            ->assertOk();

        $this->assertSame(1, $incompletos->json('recordsFiltered'));
        $this->assertStringContainsString('Ciclo Incompleto', $this->datatableRowText($incompletos->json('data')));

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.formaciones', [
                'anio' => $year,
                'mes' => 5,
                'ciclo' => 'incompleto',
            ]))
            ->assertOk()
            ->assertSee('Filtrado desde Dashboard (ciclo)', false)
            ->assertSee('Incompleto', false);
    }

    public function test_export_respects_filters_and_audits(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => '9001',
            'nombre_completo' => 'Export Incluida',
            'nombre_curso' => 'Inducción SST',
            'categoria' => 'Obligatoria',
            'anio' => 2026,
            'mes' => 2,
            'fecha_inicio' => '2026-02-01',
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => '9002',
            'nombre_completo' => 'Export Excluida',
            'nombre_curso' => 'Manejo defensivo',
            'categoria' => 'Complementaria',
            'anio' => 2025,
            'mes' => 8,
            'fecha_inicio' => '2025-08-01',
        ]);

        $viewer = $this->viewerUser();

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.formaciones.export', [
                'anio' => 2026,
                'categoria' => 'Obligatoria',
            ]))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'formacion-export-');
        file_put_contents($temp, $response->streamedContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($temp));
        $sharedStrings = (string) $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if (is_file($temp)) {
            unlink($temp);
        }

        $this->assertStringContainsString('Export Incluida', $sharedStrings);
        $this->assertStringNotContainsString('Export Excluida', $sharedStrings);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'formacion',
            'event_type' => 'export',
            'action' => 'formaciones_excel',
        ]);
    }

    public function test_datatable_export_and_options_require_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion-humana.formacion.formaciones.export'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.formacion.formaciones.options'))
            ->assertForbidden();
    }

    public function test_edit_permission_implies_view_for_datatable_and_export(): void
    {
        FormacionRegistro::factory()->create([
            'nombre_completo' => 'Edit Implies View',
        ]);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.formacion',
            'formacion.edit',
        ]);

        $this->actingAs($user)
            ->getJson(route('gestion-humana.formacion.formaciones.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($user)
            ->get(route('gestion-humana.formacion.formaciones.export'))
            ->assertOk();
    }

    /**
     * @param  list<list<string>>|null  $data
     */
    private function datatableRowText(?array $data): string
    {
        return collect($data ?? [])
            ->map(fn (array $row): string => implode(' ', $row))
            ->implode(' ');
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
