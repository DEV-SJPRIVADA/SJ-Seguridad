<?php

namespace Tests\Feature\GestionHumana;

use App\Exports\AcreditacionExportApoXlsExport;
use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionCargo;
use App\Models\AcreditacionExportApoRun;
use App\Models\AcreditacionExportApoSetting;
use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Services\GestionHumana\AcreditacionExportApoRowResolver;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AcreditacionesExportApoManualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        AcreditacionExportApoSetting::singleton();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_viewer_gets_403_on_manual_routes(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => '123',
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.acreditaciones.export-apo.manual.generate'), [
                'export_mode' => AcreditacionExportApoXlsExport::MODE_ACREDITAR,
                'rows' => [
                    ['document_number' => '123', 'nombre1' => 'A'],
                ],
            ])
            ->assertForbidden();
    }

    public function test_export_apo_opens_manual_with_document_numbers_from_validaciones(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.export-apo', [
                'mode' => 'manual',
                'document_numbers' => '111,222',
            ]))
            ->assertOk()
            ->assertViewHas('initialWorkMode', 'manual')
            ->assertViewHas('initialManualDocuments', ['111', '222']);
    }

    public function test_prefill_from_ficha_when_no_acreditado(): void
    {
        $editor = $this->editorUser();
        $cedula = '5015025035';

        EmployeeFichaProfile::query()->create([
            'document_number' => $cedula,
            'full_name' => 'Ana Maria Lopez Ruiz',
            'first_name' => 'Ana',
            'second_name' => 'Maria',
            'first_surname' => 'Lopez',
            'second_surname' => 'Ruiz',
            'birth_date' => '1992-03-20',
            'sex' => 'F',
            'hire_date' => '2021-06-01',
            'position_name' => 'Escolta',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => $cedula,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('found', true)
            ->assertJsonPath('source', 'ficha')
            ->assertJsonPath('row.document_number', $cedula)
            ->assertJsonPath('row.nombre1', 'Ana')
            ->assertJsonPath('row.apellido1', 'Lopez')
            ->assertJsonPath('row.genero', '2')
            ->assertJsonPath(
                'row.fecha_vinculacion',
                AcreditacionExportApoRowResolver::defaultFechaVinculacion('Y-m-d'),
            );
    }

    public function test_prefill_empty_row_when_document_unknown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 11:00:00', 'America/Bogota'));

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => '9998887776',
            ])
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('source', null)
            ->assertJsonPath('row.document_number', '9998887776')
            ->assertJsonPath('row.nombre1', '')
            ->assertJsonPath('row.fecha_vinculacion', '2026-10-15');
    }

    public function test_manual_generate_acreditar_skips_valida_and_uses_seq(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 11:00:00', 'America/Bogota'));

        $editor = $this->editorUser();
        $cedula = '6016026036';

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.export-apo.manual.generate'), [
                'export_mode' => AcreditacionExportApoXlsExport::MODE_ACREDITAR,
                'rows' => [[
                    'document_number' => $cedula,
                    'nombre1' => 'Pedro',
                    'nombre2' => '',
                    'apellido1' => 'Gomez',
                    'apellido2' => '',
                    'fecha_nacimiento' => '1990-05-15',
                    'genero' => '1',
                    'cargo' => 'ESCOLTA',
                    'fecha_vinculacion' => '2020-01-10',
                    'codigo_curso' => '2201',
                    'nit_escuela' => '900111222',
                    'nro' => 'NC-MANUAL',
                ]],
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel');
        $response->assertDownload('APO90057671862026101601.xls');

        $temp = tempnam(sys_get_temp_dir(), 'apo-manual-');
        file_put_contents($temp, $response->streamedContent());
        $workbook = IOFactory::load($temp);
        @unlink($temp);

        $sheet = $workbook->getSheetByName('ApoDatos');
        $this->assertNotNull($sheet);
        $this->assertSame('9005767186', (string) $sheet->getCell('A2')->getValue());
        $this->assertSame($cedula, (string) $sheet->getCell('D2')->getValue());
        $this->assertSame('Pedro', (string) $sheet->getCell('E2')->getValue());
        $this->assertSame('Gomez', (string) $sheet->getCell('G2')->getValue());
        $this->assertSame('15/05/1990', (string) $sheet->getCell('I2')->getValue());
        $this->assertSame('10/01/2020', (string) $sheet->getCell('L2')->getValue());

        $this->assertDatabaseHas('acreditacion_export_apo_runs', [
            'file_name' => 'APO90057671862026101601.xls',
            'rows_exported' => 1,
            'rows_ok' => 1,
            'rows_blocked' => 0,
        ]);
    }

    public function test_manual_generate_retirar_only_needs_cedula(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-16 12:00:00', 'America/Bogota'));

        $editor = $this->editorUser();

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.export-apo.manual.generate'), [
                'export_mode' => AcreditacionExportApoXlsExport::MODE_RETIRAR,
                'rows' => [
                    ['document_number' => '7017027037'],
                    ['document_number' => ''],
                ],
            ]);

        $response->assertOk();
        $response->assertDownload('APO90057671862026101601.xls');

        $temp = tempnam(sys_get_temp_dir(), 'apo-manual-ret-');
        file_put_contents($temp, $response->streamedContent());
        $workbook = IOFactory::load($temp);
        @unlink($temp);

        $retiros = $workbook->getSheetByName('Retiros');
        $this->assertNotNull($retiros);
        $this->assertSame('7017027037', (string) $retiros->getCell('D2')->getValue());

        $this->assertSame(1, AcreditacionExportApoRun::query()->count());
    }

    public function test_manual_generate_rejects_empty_rows(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.generate'), [
                'export_mode' => AcreditacionExportApoXlsExport::MODE_ACREDITAR,
                'rows' => [
                    ['document_number' => '', 'nombre1' => 'X'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rows']);
    }

    public function test_export_apo_page_exposes_manual_mode(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.export-apo'))
            ->assertOk()
            ->assertSee('Automática', false)
            ->assertSee('Manual', false)
            ->assertSee('Export Apo Manual', false);
    }

    public function test_prefill_prefers_acreditado_over_ficha(): void
    {
        $editor = $this->editorUser();
        $cedula = '8018028038';

        EmployeeFichaProfile::query()->create([
            'document_number' => $cedula,
            'full_name' => 'Ficha Nombre',
            'first_name' => 'Ficha',
            'first_surname' => 'Apellido',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'full_name' => 'Acreditado Nombre',
            'cargo_apo' => 'ESCOLTA',
        ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => $cedula,
            ])
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('source', 'acreditado');
    }

    public function test_manual_prefill_brings_course_even_if_vencido_but_automatic_does_not(): void
    {
        $editor = $this->editorUser();
        $cedula = '9019029039';

        $this->seedCargoCatalog();
        $this->createProfile($cedula, 'ESCOLTA');
        $tipo = $this->tipoCurso('R.ESCOLTA', '2', '3301');
        $curso = $this->curso($cedula, $tipo, now()->subYears(5)->toDateString(), 'NC-VENCIDO', '900333444');
        $this->assertSame(EmployeeCurso::VIGENCIA_VENCIDO, $curso->computeVigencia());

        $acreditado = AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => $cedula,
            'cargo' => 'ESCOLTA',
            'cargo_apo' => 'ESCOLTA',
        ]);

        // Manual: trae el curso del cargo aunque esté vencido.
        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => $cedula,
            ])
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('source', 'acreditado')
            ->assertJsonPath('row.cargo', '2')
            ->assertJsonPath('row.codigo_curso', '3301')
            ->assertJsonPath('row.nro', 'NC-VENCIDO')
            ->assertJsonPath('row.nit_escuela', '900333444')
            ->assertJsonPath('avisos', '');

        // Automática: con política VIGENTE el curso vencido no se toma.
        $resolved = app(AcreditacionExportApoRowResolver::class)
            ->resolve($acreditado, AcreditacionExportApoRowResolver::POLICY_VIGENTE);
        $this->assertSame('', $resolved['codigo_curso']);
        $this->assertContains(AcreditacionExportApoRowResolver::NOVEDAD_CURSO_VENCIDO, $resolved['motivo_codes']);
    }

    public function test_manual_prefill_from_ficha_uses_profile_cargo_number_and_ignores_other_cargo_courses(): void
    {
        $editor = $this->editorUser();
        $cedula = '1111769695';

        $this->seedCargoCatalog();
        $this->createProfile($cedula, 'GUARDA');

        // Curso de vigilancia (cargo 1) en ACTUALIZAR y uno de escolta (cargo 2) más reciente: gana vigilancia.
        $vigilancia = $this->curso($cedula, $this->tipoCurso('R.VIGILANCIA', '1', '1201'), now()->subDays(350)->toDateString(), 'NC-VIG', '900555666');
        $this->curso($cedula, $this->tipoCurso('F.ESCOLTA', '2', '2101'), now()->subDays(10)->toDateString(), 'NC-ESC', '900555666');
        $this->assertSame(EmployeeCurso::VIGENCIA_ACTUALIZAR, $vigilancia->computeVigencia());

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => $cedula,
            ])
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('source', 'ficha')
            ->assertJsonPath('row.cargo', '1')
            ->assertJsonPath('row.codigo_curso', '1201')
            ->assertJsonPath('row.nro', 'NC-VIG')
            ->assertJsonPath('row.nit_escuela', '900555666')
            ->assertJsonPath('avisos', 'Sin registro en Acreditados: se usó el cargo del perfil');
    }

    public function test_manual_prefill_picks_acreditado_matching_profile_cargo_number(): void
    {
        $editor = $this->editorUser();
        $cedula = '7101102103';

        $this->seedCargoCatalog();
        $this->createProfile($cedula, 'GUARDA MENSUAL');
        $this->curso($cedula, $this->tipoCurso('F.VIGILANCIA', '1', '1101'), now()->subMonths(2)->toDateString(), 'NC-VIG', '900555666');
        $this->curso($cedula, $this->tipoCurso('R.ESCOLTA', '2', '2201'), now()->subMonth()->toDateString(), 'NC-ESC', '900555666');

        AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => $cedula,
            'cargo' => 'GUARDA',
            'cargo_apo' => 'VIGILANTE',
        ]);
        // Registro más reciente pero de otro cargo (ESCOLTA = 2): no se elige porque el perfil es 1.
        AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => $cedula,
            'cargo' => 'GUARDA',
            'cargo_apo' => 'ESCOLTA',
        ]);

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => $cedula,
            ])
            ->assertOk()
            ->assertJsonPath('source', 'acreditado')
            ->assertJsonPath('row.cargo', '1')
            ->assertJsonPath('row.codigo_curso', '1101')
            ->assertJsonPath('row.nro', 'NC-VIG')
            ->assertJsonPath('avisos', '');
    }

    public function test_manual_prefill_warns_when_acreditado_and_profile_cargo_numbers_differ(): void
    {
        $editor = $this->editorUser();
        $cedula = '7201202203';

        $this->seedCargoCatalog();
        $this->createProfile($cedula, 'ESCOLTA');
        $this->curso($cedula, $this->tipoCurso('F.VIGILANCIA', '1', '1101'), now()->subMonth()->toDateString(), 'NC-VIG', '900555666');

        AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => $cedula,
            'cargo' => 'GUARDA',
            'cargo_apo' => 'VIGILANTE',
        ]);

        // Solo informativo: igual trae el curso del cargo del registro (vigilancia).
        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => $cedula,
            ])
            ->assertOk()
            ->assertJsonPath('row.codigo_curso', '1101')
            ->assertJsonPath('avisos', 'Cargo de Acreditados y del perfil tienen distinto cargo de acreditación');
    }

    public function test_manual_prefill_warns_when_profile_cargo_not_in_catalog(): void
    {
        $editor = $this->editorUser();
        $cedula = '7301302303';

        $this->seedCargoCatalog();
        $this->createProfile($cedula, 'GUARDAS MOTORIZADOS');
        $this->curso($cedula, $this->tipoCurso('F.VIGILANCIA', '1', '1101'), now()->subMonth()->toDateString(), 'NC-VIG', '900555666');

        $response = $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => $cedula,
            ])
            ->assertOk()
            ->assertJsonPath('source', 'ficha')
            ->assertJsonPath('row.cargo', '')
            ->assertJsonPath('row.codigo_curso', '');

        $this->assertStringContainsString('Cargo del perfil no está en Gestionar cargos', (string) $response->json('avisos'));
    }

    public function test_automatic_warns_but_keeps_valid_course_when_cargo_numbers_differ(): void
    {
        $cedula = '7401402403';

        $this->seedCargoCatalog();
        $this->createProfile($cedula, 'ESCOLTA');
        $this->curso($cedula, $this->tipoCurso('R.VIGILANCIA', '1', '1201'), now()->subMonth()->toDateString(), 'NC-VIG', '900555666');

        $acreditado = AcreditacionAcreditado::factory()->porVencer()->create([
            'document_number' => $cedula,
            'cargo' => 'GUARDA',
            'cargo_apo' => 'VIGILANTE',
        ]);

        $resolved = app(AcreditacionExportApoRowResolver::class)
            ->resolve($acreditado, AcreditacionExportApoRowResolver::POLICY_VIGENTE);

        $this->assertSame('1201', $resolved['codigo_curso']);
        $this->assertFalse($resolved['hard_block']);
        $this->assertTrue($resolved['soft_novedad']);
        $this->assertSame([AcreditacionExportApoRowResolver::NOVEDAD_CARGO_PERFIL_DISTINTO], $resolved['motivo_codes']);
    }

    /**
     * Gestionar cargos mínimo (estructura real): cargo nómina → cargo APO → número de acreditación.
     */
    private function seedCargoCatalog(): void
    {
        $rows = [
            ['GUARDA', 'VIGILANTE', '1'],
            ['GUARDA MENSUAL', 'VIGILANTE', '1'],
            ['ESCOLTA', 'ESCOLTA', '2'],
            ['SUPERVISORES', 'SUPERVISOR', '4'],
        ];

        foreach ($rows as $index => [$manager, $apo, $number]) {
            AcreditacionCargo::factory()->create([
                'cargo_manager' => $manager,
                'cargo_apo' => $apo,
                'cargo_acreditacion' => $number,
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    private function createProfile(string $cedula, string $positionName): EmployeeFichaProfile
    {
        return EmployeeFichaProfile::query()->create([
            'document_number' => $cedula,
            'full_name' => 'Nombre Completo Test',
            'first_name' => 'Nombre',
            'first_surname' => 'Apellido',
            'birth_date' => '1990-05-15',
            'sex' => 'M',
            'hire_date' => '2020-01-10',
            'position_name' => $positionName,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
    }

    private function tipoCurso(string $tipo, string $cargoAcredit, string $codigo): CursoTipo
    {
        return CursoTipo::factory()->create([
            'tipo_curso' => $tipo,
            'cargo_acredit' => $cargoAcredit,
            'cursos' => $codigo,
            'is_active' => true,
        ]);
    }

    private function curso(string $cedula, CursoTipo $tipo, string $fechaExpedicion, string $numero, string $nitEscuela): EmployeeCurso
    {
        $escuela = CursoEscuela::query()->firstOrCreate(
            ['nit' => $nitEscuela],
            CursoEscuela::factory()->make(['nit' => $nitEscuela, 'is_active' => true])->toArray(),
        );

        return EmployeeCurso::factory()->withEscuela($escuela)->create([
            'document_number' => $cedula,
            'curso_tipo_id' => $tipo->id,
            'fecha_expedicion' => $fechaExpedicion,
            'numero_curso' => $numero,
        ]);
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
