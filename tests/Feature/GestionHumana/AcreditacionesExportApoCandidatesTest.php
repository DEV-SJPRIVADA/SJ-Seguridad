<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Services\Access\AcreditacionesAccessService;
use App\Services\GestionHumana\AcreditacionExportApoRowResolver;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcreditacionesExportApoCandidatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_viewer_does_not_see_export_apo_tab(): void
    {
        $viewer = $this->viewerUser();
        $tabs = app(AcreditacionesAccessService::class)->visibleTabsFor($viewer);

        $this->assertNotContains('export_apo', $tabs);
        $this->assertNotContains('catalogo', $tabs);
        $this->assertNotContains('validaciones', $tabs);
        $this->assertContains('dashboard', $tabs);
    }

    public function test_viewer_gets_403_on_export_apo_routes(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.export-apo'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.preview'), [
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.acreditaciones.export-apo.generate'), [
                'ids' => [1],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                'include_novedades' => false,
            ])
            ->assertForbidden();
    }

    public function test_editor_validate_preview_excludes_acreditado_fresco(): void
    {
        $editor = $this->editorUser();
        $cedula = '1002003001';

        $this->createCompleteFicha($cedula);

        $enProceso = AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'cargo_apo' => 'ESCOLTA',
            'full_name' => 'Candidato Uno',
        ]);
        $porVencer = AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => $cedula,
            'cargo_apo' => 'SUPERVISOR',
            'full_name' => 'Candidato Uno',
        ]);
        AcreditacionAcreditado::factory()->create([
            'document_number' => $cedula,
            'cargo_apo' => 'VIGILANTE',
            'estado' => AcreditacionAcreditado::ESTADO_ACREDITADO,
            'full_name' => 'Candidato Uno',
        ]);

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.export-apo'))
            ->assertOk()
            ->assertSee('Export Apo', false)
            ->assertSee('Política vigencia curso', false)
            ->assertSee('NoDocumento', false)
            ->assertSee('CodigoCurso', false);

        $json = $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.preview'), [
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
            ])
            ->assertOk()
            ->json();

        $ids = collect($json['rows'] ?? [])->pluck('acreditado_id')->all();
        $this->assertCount(2, $ids);
        $this->assertContains($enProceso->id, $ids);
        $this->assertContains($porVencer->id, $ids);
        $this->assertSame(2, $json['summary']['found']);
        $this->assertArrayHasKey('nombre1', $json['rows'][0]);
        $this->assertArrayHasKey('codigo_curso', $json['rows'][0]);
        $this->assertArrayHasKey('valida', $json['rows'][0]);
    }

    public function test_resolver_escolta_accepts_f_or_r_and_ignores_other_cargo(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28'));

        $cedula = '2003004005';
        $this->createCompleteFicha($cedula, ['sex' => 'M']);

        AcreditacionCargo::factory()->create([
            'cargo_apo' => 'ESCOLTA',
            'cargo_acreditacion' => '2',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $escuela = CursoEscuela::factory()->create([
            'nit' => '900111222',
            'is_active' => true,
        ]);

        $tipoF = CursoTipo::factory()->create([
            'tipo_curso' => 'F.ESCOLTA',
            'cargo_acredit' => 'ESCOLTA',
            'cursos' => '1201',
            'is_active' => true,
        ]);
        $tipoR = CursoTipo::factory()->create([
            'tipo_curso' => 'R.ESCOLTA',
            'cargo_acredit' => '',
            'cursos' => '2201',
            'is_active' => true,
        ]);
        $tipoSupervisor = CursoTipo::factory()->create([
            'tipo_curso' => 'F.SUPERVISOR',
            'cargo_acredit' => 'SUPERVISOR',
            'cursos' => '3201',
            'is_active' => true,
        ]);

        EmployeeCurso::factory()->withEscuela($escuela)->create([
            'document_number' => $cedula,
            'curso_tipo_id' => $tipoF->id,
            'fecha_expedicion' => '2026-01-10',
            'numero_curso' => 'NC-F-001',
        ]);
        EmployeeCurso::factory()->withEscuela($escuela)->create([
            'document_number' => $cedula,
            'curso_tipo_id' => $tipoR->id,
            'fecha_expedicion' => '2026-06-15',
            'numero_curso' => 'NC-R-001',
        ]);
        EmployeeCurso::factory()->withEscuela($escuela)->create([
            'document_number' => $cedula,
            'curso_tipo_id' => $tipoSupervisor->id,
            'fecha_expedicion' => '2026-08-01',
            'numero_curso' => 'NC-SUP-001',
        ]);

        $acreditado = AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'cargo_apo' => 'ESCOLTA',
        ]);

        $resolved = app(AcreditacionExportApoRowResolver::class)->resolve(
            $acreditado,
            AcreditacionExportApoRowResolver::POLICY_VIGENTE,
        );

        $this->assertTrue($resolved['valida']);
        $this->assertSame('2201', $resolved['codigo_curso']);
        $this->assertSame('2', $resolved['cargo']);
        $this->assertSame('1', $resolved['genero']);
        $this->assertSame('NC-R-001', $resolved['nro']);
        $this->assertSame('900111222', $resolved['nit_escuela']);
        $this->assertSame('R.ESCOLTA', $resolved['tipo_curso']);
        $this->assertSame('VIGENTE', $resolved['estado_curso']);

        Carbon::setTestNow();
    }

    public function test_resolver_matches_fr_tipo_when_cargo_acredit_is_catalog_code(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28'));

        $cedula = '10188085';
        $this->createCompleteFicha($cedula, ['sex' => 'M']);

        AcreditacionCargo::factory()->create([
            'cargo_apo' => 'ESCOLTA',
            'cargo_acreditacion' => '2',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $escuela = CursoEscuela::factory()->create([
            'nit' => '900111222',
            'is_active' => true,
        ]);

        $tipoR = CursoTipo::factory()->create([
            'tipo_curso' => 'R.ESCOLTA',
            'cargo_acredit' => '2',
            'cursos' => '2201',
            'is_active' => true,
        ]);
        $tipoSupervisor = CursoTipo::factory()->create([
            'tipo_curso' => 'F.SUPERVISOR',
            'cargo_acredit' => '4',
            'cursos' => '3101',
            'is_active' => true,
        ]);

        EmployeeCurso::factory()->withEscuela($escuela)->create([
            'document_number' => $cedula,
            'curso_tipo_id' => $tipoR->id,
            'fecha_expedicion' => '2026-07-25',
            'numero_curso' => 'ECSP0015-M256100',
            'escuela_nit' => '900111222',
        ]);
        EmployeeCurso::factory()->withEscuela($escuela)->create([
            'document_number' => $cedula,
            'curso_tipo_id' => $tipoSupervisor->id,
            'fecha_expedicion' => '2026-08-01',
            'numero_curso' => 'NC-SUP-001',
        ]);

        $acreditado = AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'cargo_apo' => 'ESCOLTA',
        ]);

        $resolved = app(AcreditacionExportApoRowResolver::class)->resolve(
            $acreditado,
            AcreditacionExportApoRowResolver::POLICY_VIGENTE,
        );

        $this->assertTrue($resolved['valida']);
        $this->assertSame('2201', $resolved['codigo_curso']);
        $this->assertSame('R.ESCOLTA', $resolved['tipo_curso']);
        $this->assertSame('VIGENTE', $resolved['estado_curso']);
        $this->assertSame('ECSP0015-M256100', $resolved['nro']);
        $this->assertSame('900111222', $resolved['nit_escuela']);

        Carbon::setTestNow();
    }

    public function test_resolver_fills_nit_escuela_from_catalog_using_numero_curso_code(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28'));

        $cedula = '10188086';
        $this->createCompleteFicha($cedula, ['sex' => 'M']);

        AcreditacionCargo::factory()->create([
            'cargo_apo' => 'ESCOLTA',
            'cargo_acreditacion' => '2',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $escuela = CursoEscuela::factory()->create([
            'codigo' => '0015',
            'nit' => '800999888',
            'is_active' => true,
        ]);

        $tipoR = CursoTipo::factory()->create([
            'tipo_curso' => 'R.ESCOLTA',
            'cargo_acredit' => '2',
            'cursos' => '2201',
            'is_active' => true,
        ]);

        EmployeeCurso::factory()->create([
            'document_number' => $cedula,
            'curso_tipo_id' => $tipoR->id,
            'fecha_expedicion' => '2026-07-25',
            'numero_curso' => 'ECSP0015-M256890',
            'curso_escuela_id' => null,
            'escuela_codigo' => null,
            'escuela_nit' => null,
            'escuela_nombre' => null,
        ]);

        $acreditado = AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'cargo_apo' => 'ESCOLTA',
        ]);

        $resolved = app(AcreditacionExportApoRowResolver::class)->resolve(
            $acreditado,
            AcreditacionExportApoRowResolver::POLICY_VIGENTE,
        );

        $this->assertTrue($resolved['valida']);
        $this->assertSame('800999888', $resolved['nit_escuela']);
        $this->assertSame('ECSP0015-M256890', $resolved['nro']);
        $this->assertSame($escuela->nit, $resolved['nit_escuela']);

        Carbon::setTestNow();
    }

    public function test_ficha_incompleta_is_hard_block_in_preview(): void
    {
        $editor = $this->editorUser();
        $cedula = '3004005006';

        EmployeeFichaProfile::query()->create([
            'document_number' => $cedula,
            'full_name' => 'Incompleto Test',
            'first_name' => 'Incompleto',
            'first_surname' => '',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'sex' => 'M',
        ]);

        AcreditacionCargo::factory()->create([
            'cargo_apo' => 'ESCOLTA',
            'cargo_acreditacion' => '2',
            'is_active' => true,
        ]);

        $acreditado = AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'cargo_apo' => 'ESCOLTA',
        ]);

        $response = $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.preview'), [
                'ids' => [$acreditado->id],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
            ])
            ->assertOk()
            ->json();

        $this->assertSame(1, $response['summary']['bloqueadas']);
        $this->assertSame(0, $response['summary']['validas']);
        $this->assertFalse($response['rows'][0]['valida']);
        $this->assertTrue($response['rows'][0]['hard_block']);
        $this->assertContains(
            AcreditacionExportApoRowResolver::NOVEDAD_FICHA_INCOMPLETA,
            $response['rows'][0]['motivo_codes'],
        );
    }

    public function test_editor_preview_without_ids_loads_all_candidates(): void
    {
        $editor = $this->editorUser();
        $cedula = '4005006007';
        $this->createCompleteFicha($cedula);

        $acreditado = AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'cargo_apo' => 'ESCOLTA',
        ]);

        $response = $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.preview'), [
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
            ])
            ->assertOk()
            ->json();

        $ids = collect($response['rows'] ?? [])->pluck('acreditado_id')->all();
        $this->assertContains($acreditado->id, $ids);
        $this->assertGreaterThanOrEqual(1, $response['summary']['found']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCompleteFicha(string $documentNumber, array $overrides = []): EmployeeFichaProfile
    {
        return EmployeeFichaProfile::query()->create(array_merge([
            'document_number' => $documentNumber,
            'full_name' => 'Nombre Completo Test',
            'first_name' => 'Nombre',
            'second_name' => 'Segundo',
            'first_surname' => 'Apellido',
            'second_surname' => 'Dos',
            'birth_date' => '1990-05-15',
            'sex' => 'M',
            'hire_date' => '2020-01-10',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ], $overrides));
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
