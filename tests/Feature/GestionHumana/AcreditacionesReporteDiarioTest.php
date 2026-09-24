<?php

namespace Tests\Feature\GestionHumana;

use App\Models\AcreditacionAcreditado;
use App\Models\AcreditacionReporteDiarioCarga;
use App\Models\AcreditacionReporteDiarioFila;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AcreditacionesReporteDiarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-24', 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_and_without_permission_are_forbidden(): void
    {
        $this->get(route('gestion-humana.acreditaciones.reporte-diario'))
            ->assertRedirect();

        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.acreditaciones.reporte-diario'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion-humana.acreditaciones.reporte-diario.datatable'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion-humana.acreditaciones.reporte-diario.export'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('gestion-humana.acreditaciones.reporte-diario.cargas'))
            ->assertForbidden();
    }

    public function test_viewer_can_get_index_datatable_export_cargas_but_not_import(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.reporte-diario'))
            ->assertOk()
            ->assertSee('Reporte Diario', false)
            ->assertDontSee('Cargar reporte APO', false)
            ->assertDontSee('name="file_proceso"', false);

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.reporte-diario.datatable', [
                'fecha_reporte' => '2026-09-24',
            ]))
            ->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.acreditaciones.reporte-diario.export', [
                'fecha_reporte' => '2026-09-24',
            ]))
            ->assertOk();

        $this->actingAs($viewer)
            ->getJson(route('gestion-humana.acreditaciones.reporte-diario.cargas'))
            ->assertOk()
            ->assertJsonStructure(['data']);

        $path = $this->makeProcesoFile([
            ['Perez', 'Lopez', 'Juan', 'Carlos', '1001', 'GUARDA', 'EN PROCESO'],
        ]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile($path, 'proceso.xlsx', null, null, true),
            ])
            ->assertForbidden();
    }

    public function test_edit_imports_one_origin_on_new_day(): void
    {
        $editor = $this->editorUser();
        $path = $this->makeProcesoFile([
            ['Perez', 'Lopez', 'Juan', 'Carlos', '1001', 'GUARDA', 'EN PROCESO'],
            ['Garcia', 'Ruiz', 'Ana', '', '1002', 'ESCOLTA', 'REVISION'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile($path, 'proceso.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.acreditaciones.reporte-diario', [
                'fecha_reporte' => '2026-09-24',
            ]))
            ->assertSessionHas('import_done');

        $carga = AcreditacionReporteDiarioCarga::query()
            ->whereDate('fecha_reporte', '2026-09-24')
            ->firstOrFail();

        $this->assertSame(2, (int) $carga->proceso_rows_ok);
        $this->assertNull($carga->acreditado_loaded_at);
        $this->assertSame(0, (int) $carga->acreditado_rows_ok);

        $this->assertSame(2, AcreditacionReporteDiarioFila::query()
            ->where('carga_id', $carga->id)
            ->where('origen', AcreditacionReporteDiarioFila::ORIGEN_PROCESO)
            ->count());

        $this->assertSame(0, AcreditacionReporteDiarioFila::query()
            ->where('carga_id', $carga->id)
            ->where('origen', AcreditacionReporteDiarioFila::ORIGEN_ACREDITADO)
            ->count());

        $this->assertSame(0, AcreditacionAcreditado::query()->count());
    }

    public function test_edit_imports_both_origins(): void
    {
        $editor = $this->editorUser();
        $proceso = $this->makeProcesoFile([
            ['Perez', 'Lopez', 'Juan', '', '2001', 'GUARDA', 'EN PROCESO'],
        ]);
        $acreditado = $this->makeAcreditadoFile([
            ['Gomez', 'Diaz', 'Maria', '', '2002', 'ESCOLTA', '2027-06-01'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile($proceso, 'proceso.xlsx', null, null, true),
                'file_acreditado' => new UploadedFile($acreditado, 'acreditado.xlsx', null, null, true),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_done');

        $carga = AcreditacionReporteDiarioCarga::query()
            ->whereDate('fecha_reporte', '2026-09-24')
            ->firstOrFail();

        $this->assertSame(1, (int) $carga->proceso_rows_ok);
        $this->assertSame(1, (int) $carga->acreditado_rows_ok);
        $this->assertNotNull($carga->proceso_loaded_at);
        $this->assertNotNull($carga->acreditado_loaded_at);

        $this->assertDatabaseHas('acreditacion_reporte_diario_filas', [
            'document_number' => '2001',
            'origen' => 'PROCESO',
            'estado_apo' => 'EN PROCESO',
        ]);

        $this->assertDatabaseHas('acreditacion_reporte_diario_filas', [
            'document_number' => '2002',
            'origen' => 'ACREDITADO',
            'vigencia_acr' => '2027-06-01',
            'estado_apo' => 'ACREDITADO',
        ]);
    }

    public function test_partial_reimport_preserves_other_origin(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile(
                    $this->makeProcesoFile([
                        ['Perez', 'Lopez', 'Juan', '', '3001', 'GUARDA', 'OLD'],
                    ]),
                    'proceso.xlsx',
                    null,
                    null,
                    true,
                ),
                'file_acreditado' => new UploadedFile(
                    $this->makeAcreditadoFile([
                        ['Gomez', 'Diaz', 'Maria', '', '3002', 'ESCOLTA', '2027-01-01'],
                    ]),
                    'acreditado.xlsx',
                    null,
                    null,
                    true,
                ),
            ])
            ->assertRedirect();

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile(
                    $this->makeProcesoFile([
                        ['Nuevo', 'Apellido', 'Nombre', '', '3003', 'SUPERVISOR', 'NEW'],
                    ]),
                    'proceso2.xlsx',
                    null,
                    null,
                    true,
                ),
                'confirm_replace' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('import_done');

        $carga = AcreditacionReporteDiarioCarga::query()
            ->whereDate('fecha_reporte', '2026-09-24')
            ->firstOrFail();

        $this->assertSame(1, AcreditacionReporteDiarioFila::query()
            ->where('carga_id', $carga->id)
            ->where('origen', 'PROCESO')
            ->count());

        $this->assertDatabaseHas('acreditacion_reporte_diario_filas', [
            'document_number' => '3003',
            'origen' => 'PROCESO',
            'estado_apo' => 'NEW',
        ]);

        $this->assertDatabaseMissing('acreditacion_reporte_diario_filas', [
            'document_number' => '3001',
        ]);

        $this->assertDatabaseHas('acreditacion_reporte_diario_filas', [
            'document_number' => '3002',
            'origen' => 'ACREDITADO',
        ]);

        $this->assertSame(1, (int) $carga->fresh()->acreditado_rows_ok);
        $this->assertNotNull($carga->fresh()->acreditado_loaded_at);
    }

    public function test_replace_without_confirm_does_not_mutate(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile(
                    $this->makeProcesoFile([
                        ['Perez', 'Lopez', 'Juan', '', '4001', 'GUARDA', 'OLD'],
                    ]),
                    'proceso.xlsx',
                    null,
                    null,
                    true,
                ),
            ])
            ->assertRedirect();

        $this->actingAs($editor)
            ->from(route('gestion-humana.acreditaciones.reporte-diario'))
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile(
                    $this->makeProcesoFile([
                        ['Nuevo', 'X', 'Y', '', '4002', 'GUARDA', 'NEW'],
                    ]),
                    'proceso2.xlsx',
                    null,
                    null,
                    true,
                ),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('confirm_replace');

        $message = (string) session('errors')->first('confirm_replace');
        $this->assertStringContainsString('Confirmar reemplazo', $message);

        $this->assertDatabaseHas('acreditacion_reporte_diario_filas', [
            'document_number' => '4001',
            'estado_apo' => 'OLD',
        ]);

        $this->assertDatabaseMissing('acreditacion_reporte_diario_filas', [
            'document_number' => '4002',
        ]);
    }

    public function test_replace_with_confirm_replaces_origin_rows(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile(
                    $this->makeProcesoFile([
                        ['Perez', 'Lopez', 'Juan', '', '5001', 'GUARDA', 'OLD'],
                    ]),
                    'proceso.xlsx',
                    null,
                    null,
                    true,
                ),
            ])
            ->assertRedirect();

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'confirm_replace' => true,
                'file_proceso' => new UploadedFile(
                    $this->makeProcesoFile([
                        ['Nuevo', 'X', 'Y', '', '5002', 'GUARDA', 'NEW'],
                    ]),
                    'proceso2.xlsx',
                    null,
                    null,
                    true,
                ),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_done');

        $this->assertDatabaseMissing('acreditacion_reporte_diario_filas', [
            'document_number' => '5001',
        ]);

        $this->assertDatabaseHas('acreditacion_reporte_diario_filas', [
            'document_number' => '5002',
            'estado_apo' => 'NEW',
        ]);
    }

    public function test_future_date_is_rejected(): void
    {
        $editor = $this->editorUser();
        $path = $this->makeProcesoFile([
            ['Perez', 'Lopez', 'Juan', '', '6001', 'GUARDA', 'EN PROCESO'],
        ]);

        $this->actingAs($editor)
            ->from(route('gestion-humana.acreditaciones.reporte-diario'))
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-25',
                'file_proceso' => new UploadedFile($path, 'proceso.xlsx', null, null, true),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('fecha_reporte');

        $this->assertSame(0, AcreditacionReporteDiarioCarga::query()->count());
    }

    public function test_invalid_headers_abort_without_mutating(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-23',
                'file_proceso' => new UploadedFile(
                    $this->makeProcesoFile([
                        ['Perez', 'Lopez', 'Juan', '', '7001', 'GUARDA', 'OK'],
                    ]),
                    'proceso.xlsx',
                    null,
                    null,
                    true,
                ),
            ])
            ->assertRedirect();

        $bad = $this->makeSpreadsheet(
            ['Wrong', 'Headers', 'Only'],
            [['a', 'b', 'c']],
        );

        $this->actingAs($editor)
            ->from(route('gestion-humana.acreditaciones.reporte-diario'))
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile($bad, 'bad.xlsx', null, null, true),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file_proceso');

        $errors = session('errors');
        $this->assertNotNull($errors);
        $message = (string) $errors->first('file_proceso');
        $this->assertStringContainsString('Faltan columnas obligatorias', $message);
        $this->assertStringContainsString('Wrong', $message);

        $this->assertSame(0, AcreditacionReporteDiarioCarga::query()
            ->whereDate('fecha_reporte', '2026-09-24')
            ->count());
    }

    public function test_row_without_idnum_is_skipped_and_rest_ok(): void
    {
        $editor = $this->editorUser();
        $path = $this->makeProcesoFile([
            ['Perez', 'Lopez', 'Juan', '', '', 'GUARDA', 'EN PROCESO'],
            ['Garcia', 'Ruiz', 'Ana', '', '8002', 'ESCOLTA', 'OK'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile($path, 'proceso.xlsx', null, null, true),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_failures');

        $carga = AcreditacionReporteDiarioCarga::query()
            ->whereDate('fecha_reporte', '2026-09-24')
            ->firstOrFail();

        $this->assertSame(1, (int) $carga->proceso_rows_ok);
        $this->assertSame(1, (int) $carga->proceso_rows_fail);
        $this->assertDatabaseHas('acreditacion_reporte_diario_filas', [
            'document_number' => '8002',
        ]);
    }

    public function test_cedula_not_in_ficha_is_still_saved(): void
    {
        $editor = $this->editorUser();
        $path = $this->makeProcesoFile([
            ['Sin', 'Ficha', 'Persona', '', '999999', 'GUARDA', 'EN PROCESO'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.acreditaciones.reporte-diario.import'), [
                'fecha_reporte' => '2026-09-24',
                'file_proceso' => new UploadedFile($path, 'proceso.xlsx', null, null, true),
            ])
            ->assertRedirect()
            ->assertSessionHas('import_done');

        $this->assertDatabaseHas('acreditacion_reporte_diario_filas', [
            'document_number' => '999999',
            'full_name' => 'Sin Ficha Persona',
            'estado_apo' => 'EN PROCESO',
        ]);

        $this->assertSame(0, AcreditacionAcreditado::query()->count());
    }

    public function test_datatable_and_export_respect_filters(): void
    {
        $editor = $this->editorUser();
        $carga = AcreditacionReporteDiarioCarga::factory()->create([
            'fecha_reporte' => '2026-09-24',
        ]);
        AcreditacionReporteDiarioFila::factory()->proceso()->create([
            'carga_id' => $carga->id,
            'document_number' => '111',
            'full_name' => 'Alpha Proceso',
            'cargo' => 'GUARDA',
        ]);
        AcreditacionReporteDiarioFila::factory()->acreditado()->create([
            'carga_id' => $carga->id,
            'document_number' => '222',
            'full_name' => 'Beta Acreditado',
            'cargo' => 'ESCOLTA',
        ]);

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.acreditaciones.reporte-diario.datatable', [
                'fecha_reporte' => '2026-09-24',
                'origen' => 'PROCESO',
            ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.acreditaciones.reporte-diario.export', [
                'fecha_reporte' => '2026-09-24',
                'origen' => 'ACREDITADO',
            ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheet',
            (string) $response->headers->get('content-type'),
        );
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function makeProcesoFile(array $rows): string
    {
        return $this->makeSpreadsheet(
            ['Apellido1', 'Apellido2', 'Nombre1', 'Nombre2', 'IdNum', 'Cargo', 'Estado'],
            $rows,
        );
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function makeAcreditadoFile(array $rows): string
    {
        return $this->makeSpreadsheet(
            ['Apellido1', 'Apellido2', 'Nombre1', 'Nombre2', 'IdNum', 'Cargo', 'Vigen.Acr'],
            $rows,
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function makeSpreadsheet(array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue([1, 1], 'Informacion de Companias — titulo APO');

        foreach ($headers as $i => $header) {
            $sheet->setCellValue([$i + 1, 2], $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 3], $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'rd_apo_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
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
