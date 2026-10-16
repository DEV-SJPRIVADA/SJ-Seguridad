<?php

namespace Tests\Feature\GestionHumana;

use App\Exports\AcreditacionExportApoXlsExport;
use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionExportApoRun;
use App\Models\AcreditacionExportApoSetting;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
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
            ->assertJsonPath('row.genero', '2');
    }

    public function test_prefill_empty_row_when_document_unknown(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->postJson(route('gestion-humana.acreditaciones.export-apo.manual.prefill'), [
                'document_number' => '9998887776',
            ])
            ->assertOk()
            ->assertJsonPath('found', false)
            ->assertJsonPath('source', null)
            ->assertJsonPath('row.document_number', '9998887776')
            ->assertJsonPath('row.nombre1', '');
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
