<?php

namespace Tests\Feature\GestionHumana;

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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AcreditacionesExportApoGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        AcreditacionExportApoSetting::singleton();
    }

    public function test_viewer_gets_403_on_generate(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.acreditaciones.export-apo.generate'), [
                'ids' => [1],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                'include_novedades' => false,
            ])
            ->assertForbidden();
    }

    public function test_generate_filename_pattern_and_no_valida_column(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 10:00:00', 'America/Bogota'));

        $editor = $this->editorUser();
        $cedula = '4005006007';
        $acreditado = $this->seedValidCandidate($cedula, 'ESCOLTA', '2', '2201');

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.export-apo.generate'), [
                'ids' => [$acreditado->id],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                'include_novedades' => false,
            ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel');
        $response->assertDownload('APO900576718620260928001.xls');

        $temp = tempnam(sys_get_temp_dir(), 'apo-xls-');
        file_put_contents($temp, $response->streamedContent());
        $sheet = IOFactory::load($temp)->getActiveSheet();
        @unlink($temp);

        $headers = [];
        for ($col = 1; $col <= 30; $col++) {
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col).'1')->getValue());
            if ($value === '') {
                break;
            }
            $headers[] = $value;
        }

        $expected = config('acreditaciones.export_apo.headers');
        $this->assertSame($expected, $headers);
        $this->assertNotContains('Valida', $headers);
        $this->assertNotContains('novedad', $headers);
        $this->assertNotContains('motivo', array_map('strtolower', $headers));
        $this->assertCount(24, $headers);
        $this->assertSame(1, $sheet->getParent()->getSheetCount());
        $this->assertSame('ApoDatos', $sheet->getTitle());

        $this->assertSame('9005767186', (string) $sheet->getCell('A2')->getValue());
        $this->assertSame($cedula, (string) $sheet->getCell('D2')->getValue());
        $this->assertSame('15/05/1990', (string) $sheet->getCell('I2')->getValue());
        $this->assertSame('10/01/2020', (string) $sheet->getCell('L2')->getValue());
        $this->assertSame('2201', (string) $sheet->getCell('M2')->getValue());

        $this->assertDatabaseHas('acreditacion_export_apo_runs', [
            'file_name' => 'APO900576718620260928001.xls',
            'seq' => 1,
            'rows_exported' => 1,
            'rows_ok' => 1,
            'rows_blocked' => 0,
            'include_novedades' => 0,
        ]);

        Carbon::setTestNow();
    }

    public function test_seq_increments_same_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 11:00:00', 'America/Bogota'));

        $editor = $this->editorUser();
        $a = $this->seedValidCandidate('5006007008', 'ESCOLTA', '2', '1201');
        $b = $this->seedValidCandidate('5006007009', 'VIGILANTE', '1', '3201');

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.export-apo.generate'), [
                'ids' => [$a->id],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                'include_novedades' => false,
            ])
            ->assertOk()
            ->assertDownload('APO900576718620260928001.xls');

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.export-apo.generate'), [
                'ids' => [$b->id],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                'include_novedades' => false,
            ])
            ->assertOk()
            ->assertDownload('APO900576718620260928002.xls');

        $this->assertSame(2, AcreditacionExportApoRun::query()->whereDate('export_date', '2026-09-28')->count());

        Carbon::setTestNow();
    }

    public function test_hard_block_ficha_incompleta_never_exported_even_with_novedades(): void
    {
        $editor = $this->editorUser();
        $cedula = '6007008001';

        EmployeeFichaProfile::query()->create([
            'document_number' => $cedula,
            'full_name' => 'Incompleto Generate',
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

        $this->actingAs($editor)
            ->from(route('gestion-humana.acreditaciones.export-apo'))
            ->post(route('gestion-humana.acreditaciones.export-apo.generate'), [
                'ids' => [$acreditado->id],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                'include_novedades' => true,
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.export-apo'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('acreditacion_export_apo_runs', 0);
    }

    public function test_include_novedades_false_exports_only_ok_rows(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 12:00:00', 'America/Bogota'));

        $editor = $this->editorUser();
        $ok = $this->seedValidCandidate('7008009001', 'ESCOLTA', '2', '2201');
        $soft = $this->seedSoftNovedadCandidate('7008009002', 'SUPERVISOR');

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.export-apo.generate'), [
                'ids' => [$ok->id, $soft->id],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                'include_novedades' => false,
            ]);

        $response->assertOk();
        $response->assertDownload('APO900576718620260928001.xls');

        $temp = tempnam(sys_get_temp_dir(), 'apo-xls-');
        file_put_contents($temp, $response->streamedContent());
        $sheet = IOFactory::load($temp)->getActiveSheet();
        @unlink($temp);

        $this->assertSame('7008009001', (string) $sheet->getCell('D2')->getValue());
        $this->assertSame('', trim((string) $sheet->getCell('D3')->getValue()));

        $this->assertDatabaseHas('acreditacion_export_apo_runs', [
            'rows_selected' => 2,
            'rows_ok' => 1,
            'rows_novedad' => 1,
            'rows_exported' => 1,
            'include_novedades' => 0,
        ]);

        Carbon::setTestNow();
    }

    public function test_include_novedades_true_exports_ok_and_soft(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-28 13:00:00', 'America/Bogota'));

        $editor = $this->editorUser();
        $ok = $this->seedValidCandidate('8009001001', 'ESCOLTA', '2', '2201');
        $soft = $this->seedSoftNovedadCandidate('8009001002', 'SUPERVISOR');

        $response = $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.export-apo.generate'), [
                'ids' => [$ok->id, $soft->id],
                'vigencia_policy' => AcreditacionExportApoRowResolver::POLICY_VIGENTE,
                'include_novedades' => true,
            ]);

        $response->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'apo-xls-');
        file_put_contents($temp, $response->streamedContent());
        $sheet = IOFactory::load($temp)->getActiveSheet();
        @unlink($temp);

        $docs = [
            (string) $sheet->getCell('D2')->getValue(),
            (string) $sheet->getCell('D3')->getValue(),
        ];
        sort($docs);
        $this->assertSame(['8009001001', '8009001002'], $docs);

        $this->assertDatabaseHas('acreditacion_export_apo_runs', [
            'rows_selected' => 2,
            'rows_ok' => 1,
            'rows_novedad' => 1,
            'rows_exported' => 2,
            'include_novedades' => 1,
        ]);

        Carbon::setTestNow();
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

    private function seedValidCandidate(
        string $cedula,
        string $cargoApo,
        string $cargoAcreditacion,
        string $codigoCurso,
    ): AcreditacionAcreditado {
        $this->createCompleteFicha($cedula);

        AcreditacionCargo::factory()->create([
            'cargo_apo' => $cargoApo,
            'cargo_acreditacion' => $cargoAcreditacion,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $escuela = CursoEscuela::factory()->create([
            'nit' => '900111222',
            'is_active' => true,
        ]);

        $tipo = CursoTipo::factory()->create([
            'tipo_curso' => 'R.'.$cargoApo,
            'cargo_acredit' => $cargoApo,
            'cursos' => $codigoCurso,
            'is_active' => true,
        ]);

        EmployeeCurso::factory()->withEscuela($escuela)->create([
            'document_number' => $cedula,
            'curso_tipo_id' => $tipo->id,
            'fecha_expedicion' => '2026-06-15',
            'numero_curso' => 'NC-'.$cedula,
        ]);

        return AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'cargo_apo' => $cargoApo,
        ]);
    }

    /**
     * Candidato con ficha completa pero sin curso match → novedad blanda.
     */
    private function seedSoftNovedadCandidate(string $cedula, string $cargoApo): AcreditacionAcreditado
    {
        $this->createCompleteFicha($cedula);

        AcreditacionCargo::factory()->create([
            'cargo_apo' => $cargoApo,
            'cargo_acreditacion' => '4',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return AcreditacionAcreditado::factory()->enProceso()->create([
            'document_number' => $cedula,
            'cargo_apo' => $cargoApo,
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
