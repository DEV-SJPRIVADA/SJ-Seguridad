<?php

namespace Tests\Feature\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoSolicitud;
use App\Models\ClienteInternoTipoSolicitud;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ClienteInternoImportTest extends TestCase
{
    use RefreshDatabase;

    private ClienteInternoTipoSolicitud $tipo;

    private ClienteInternoEstado $estado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();

        $this->tipo = ClienteInternoTipoSolicitud::factory()->create([
            'code' => 'CERT_LAB',
            'name' => 'Certificado laboral',
        ]);
        $this->estado = ClienteInternoEstado::query()->where('code', 'PENDIENTE')->firstOrFail();
    }

    public function test_import_template_requires_edit(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.solicitudes.import-template'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('gestion-humana.cliente-interno.solicitudes.import-template'))
            ->assertOk();
    }

    public function test_import_template_has_exact_spanish_headers(): void
    {
        $editor = $this->editorUser();

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.cliente-interno.solicitudes.import-template'))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'ci-tpl-');
        file_put_contents($temp, $response->streamedContent());

        $sheet = IOFactory::load($temp)->getActiveSheet();
        $labels = array_values(config('cliente_interno.import.columns'));

        foreach ($labels as $i => $label) {
            $this->assertSame(
                $label,
                (string) $sheet->getCell([$i + 1, 1])->getValue(),
            );
        }

        if (is_file($temp)) {
            unlink($temp);
        }
    }

    public function test_period_count_requires_edit_and_returns_count(): void
    {
        ClienteInternoSolicitud::factory()->count(2)->create([
            'tipo_solicitud_id' => $this->tipo->id,
            'anio' => 2026,
            'mes' => 3,
            'fecha_solicitud' => '2026-03-10',
        ]);
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $this->tipo->id,
            'anio' => 2026,
            'mes' => 4,
            'fecha_solicitud' => '2026-04-01',
        ]);

        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cliente-interno.solicitudes.period-count', [
                'anio' => 2026,
                'mes' => 3,
            ]))
            ->assertForbidden();

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.cliente-interno.solicitudes.period-count', [
                'anio' => 2026,
                'mes' => 3,
            ]))
            ->assertOk()
            ->assertJson([
                'anio' => 2026,
                'mes' => 3,
                'count' => 2,
            ]);
    }

    public function test_import_replace_period_option_b_with_spillover(): void
    {
        // Periodo a reemplazar (marzo 2026): se borran.
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $this->tipo->id,
            'cedula' => 'OLD-MAR',
            'anio' => 2026,
            'mes' => 3,
            'fecha_solicitud' => '2026-03-05',
        ]);
        // Otro periodo: intacto.
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $this->tipo->id,
            'cedula' => 'KEEP-FEB',
            'anio' => 2026,
            'mes' => 2,
            'fecha_solicitud' => '2026-02-10',
        ]);

        $path = $this->makeImportFile([
            // In-periodo
            ['2026-03-15', 'Ana Nueva', 'NEW-1', 'ana@example.com', 'Certificado laboral', '2026-03-19', 'PENDIENTE', 'Nota A', ''],
            // Spillover fuera de periodo (opción B)
            ['2026-04-02', 'Luis Spill', 'NEW-2', '', 'CERT_LAB', '', 'Pendiente', '', '7'],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cliente-interno.solicitudes.import'), [
                'import_file' => new UploadedFile($path, 'cliente-interno.xlsx', null, null, true),
                'anio' => 2026,
                'mes' => 3,
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('cliente_interno_solicitudes', ['cedula' => 'OLD-MAR']);
        $this->assertDatabaseHas('cliente_interno_solicitudes', ['cedula' => 'KEEP-FEB']);
        $this->assertDatabaseHas('cliente_interno_solicitudes', [
            'cedula' => 'NEW-1',
            'nombre_apellidos' => 'Ana Nueva',
            'anio' => 2026,
            'mes' => 3,
            'tipo_solicitud_id' => $this->tipo->id,
            'estado_id' => $this->estado->id,
            'dias_respuesta' => 4, // lun 15 → vie 19 = 4 hábiles
            'dias_respuesta_manual' => 0,
        ]);
        $this->assertDatabaseHas('cliente_interno_solicitudes', [
            'cedula' => 'NEW-2',
            'anio' => 2026,
            'mes' => 4,
            'dias_respuesta' => 7,
            'dias_respuesta_manual' => 1,
            'estado_id' => $this->estado->id,
        ]);

        $this->assertSame(3, ClienteInternoSolicitud::query()->count());

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'cliente_interno',
            'event_type' => 'import',
            'action' => 'import_replace_period',
        ]);
    }

    public function test_import_does_not_wipe_on_validation_errors(): void
    {
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $this->tipo->id,
            'cedula' => 'KEEP-OK',
            'anio' => 2026,
            'mes' => 5,
            'fecha_solicitud' => '2026-05-01',
        ]);

        $path = $this->makeImportFile([
            ['2026-05-10', '', 'NO-NAME', '', 'Certificado laboral', '', '', '', ''],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.cliente-interno.solicitudes'))
            ->post(route('gestion-humana.cliente-interno.solicitudes.import'), [
                'import_file' => new UploadedFile($path, 'bad.xlsx', null, null, true),
                'anio' => 2026,
                'mes' => 5,
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertSessionHasErrors('import_file');

        $this->assertSame(1, ClienteInternoSolicitud::query()->count());
        $this->assertDatabaseHas('cliente_interno_solicitudes', ['cedula' => 'KEEP-OK']);
    }

    public function test_import_does_not_wipe_on_unknown_tipo(): void
    {
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $this->tipo->id,
            'cedula' => 'KEEP-TIPO',
            'anio' => 2026,
            'mes' => 6,
            'fecha_solicitud' => '2026-06-01',
        ]);

        $path = $this->makeImportFile([
            ['2026-06-10', 'Persona', 'X-1', '', 'Tipo Inexistente', '', '', '', ''],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.cliente-interno.solicitudes'))
            ->post(route('gestion-humana.cliente-interno.solicitudes.import'), [
                'import_file' => new UploadedFile($path, 'bad-tipo.xlsx', null, null, true),
                'anio' => 2026,
                'mes' => 6,
                'confirm_replace' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseHas('cliente_interno_solicitudes', ['cedula' => 'KEEP-TIPO']);
    }

    public function test_import_rejects_empty_file_without_wipe(): void
    {
        ClienteInternoSolicitud::factory()->create([
            'tipo_solicitud_id' => $this->tipo->id,
            'cedula' => 'KEEP-EMPTY',
            'anio' => 2026,
            'mes' => 7,
            'fecha_solicitud' => '2026-07-01',
        ]);

        $path = $this->makeImportFile([
            ['', '', '', '', '', '', '', '', ''],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.cliente-interno.solicitudes'))
            ->post(route('gestion-humana.cliente-interno.solicitudes.import'), [
                'import_file' => new UploadedFile($path, 'empty.xlsx', null, null, true),
                'anio' => 2026,
                'mes' => 7,
                'confirm_replace' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('import_file');

        $this->assertDatabaseHas('cliente_interno_solicitudes', ['cedula' => 'KEEP-EMPTY']);
    }

    public function test_import_requires_edit_permission(): void
    {
        $viewer = $this->viewerUser();
        $path = $this->makeImportFile([
            ['2026-01-01', 'Viewer Block', 'V-1', '', 'Certificado laboral', '', '', '', ''],
        ]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cliente-interno.solicitudes.import'), [
                'import_file' => new UploadedFile($path, 'blocked.xlsx', null, null, true),
                'anio' => 2026,
                'mes' => 1,
                'confirm_replace' => '1',
            ])
            ->assertForbidden();

        $this->assertSame(0, ClienteInternoSolicitud::query()->count());
    }

    public function test_solicitudes_page_shows_import_for_editor_hides_for_viewer(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertOk()
            ->assertDontSee('cliente-interno-import', false)
            ->assertDontSee('Plantilla e importar', false);

        $this->actingAs($editor)
            ->get(route('gestion-humana.cliente-interno.solicitudes'))
            ->assertOk()
            ->assertSee('cliente-interno-import', false)
            ->assertSee("\$dispatch('open-modal', 'cliente-interno-import')", false)
            ->assertSee(route('gestion-humana.cliente-interno.solicitudes.import-template'), false)
            ->assertSee('periodo seleccionado', false);
    }

    /**
     * @param  list<list<string|float|null>>  $rows
     */
    private function makeImportFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $labels = array_values(config('cliente_interno.import.columns'));

        foreach ($labels as $i => $label) {
            $sheet->setCellValue([$i + 1, 1], $label);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'ci-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.solicitudes.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.solicitudes.view',
            'cliente_interno.solicitudes.edit',
        ]);

        return $user;
    }
}
