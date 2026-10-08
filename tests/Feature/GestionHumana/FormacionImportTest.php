<?php

namespace Tests\Feature\GestionHumana;

use App\Models\FormacionRegistro;
use App\Models\User;
use App\Services\GestionHumana\FormacionImportService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class FormacionImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_parse_fecha_spanish_text(): void
    {
        $service = app(FormacionImportService::class);
        $fecha = $service->parseFechaInicio('jueves, 4 de junio de 2026, 00:00');

        $this->assertNotNull($fecha);
        $this->assertSame('2026-06-04', $fecha->toDateString());
        $this->assertSame(6, (int) $fecha->month);
        $this->assertSame(2026, (int) $fecha->year);
    }

    public function test_parse_fecha_y_m_d(): void
    {
        $service = app(FormacionImportService::class);
        $fecha = $service->parseFechaInicio('2025-12-01');

        $this->assertNotNull($fecha);
        $this->assertSame('2025-12-01', $fecha->toDateString());
    }

    public function test_parse_fecha_excel_serial(): void
    {
        $service = app(FormacionImportService::class);
        $serial = ExcelDate::PHPToExcel(\DateTime::createFromFormat('Y-m-d', '2026-03-15'));
        $fecha = $service->parseFechaInicio($serial);

        $this->assertNotNull($fecha);
        $this->assertSame('2026-03-15', $fecha->toDateString());
    }

    public function test_import_template_requires_edit(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.formaciones.import-template'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('gestion-humana.formacion.formaciones.import-template'))
            ->assertOk();
    }

    public function test_import_template_has_exact_spanish_headers(): void
    {
        $editor = $this->editorUser();

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.formacion.formaciones.import-template'))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'formacion-tpl-');
        file_put_contents($temp, $response->streamedContent());

        $sheet = IOFactory::load($temp)->getActiveSheet();
        $labels = array_values(config('formacion.import.columns'));

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

    public function test_import_replace_all_success(): void
    {
        FormacionRegistro::factory()->count(3)->create([
            'nombre_completo' => 'Registro Viejo',
        ]);

        $path = $this->makeImportFile([
            ['1001', 'Ana Nueva', '2026-06-04', 'Inducción SST', 'Aprobado', 'Obligatoria'],
            ['1002', 'Luis Nuevo', 'jueves, 4 de junio de 2026, 00:00', 'Primeros auxilios', '', 'Complementaria'],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'formacion.xlsx', null, null, true),
                'mode' => 'all',
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHas('status');

        $this->assertSame(2, FormacionRegistro::query()->count());
        $this->assertDatabaseMissing('formacion_registros', ['nombre_completo' => 'Registro Viejo']);
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => '1001',
            'nombre_completo' => 'Ana Nueva',
            'fecha_inicio' => '2026-06-04',
            'mes' => 6,
            'anio' => 2026,
            'nombre_curso' => 'Inducción SST',
            'calificacion' => 'Aprobado',
            'categoria' => 'Obligatoria',
        ]);
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => '1002',
            'fecha_inicio' => '2026-06-04',
            'mes' => 6,
            'anio' => 2026,
            'calificacion' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'formacion',
            'event_type' => 'import',
            'action' => 'import_replace',
        ]);
    }

    public function test_import_does_not_wipe_on_bad_headers(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => 'KEEP-1',
            'nombre_completo' => 'Debe Permanecer',
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue([1, 1], 'Columna Incorrecta');
        $sheet->setCellValue([1, 2], 'dato');

        $path = tempnam(sys_get_temp_dir(), 'formacion-bad-hdr-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.formacion.formaciones'))
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'bad.xlsx', null, null, true),
                'mode' => 'all',
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHasErrors('import_file');

        $this->assertSame(1, FormacionRegistro::query()->count());
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => 'KEEP-1',
            'nombre_completo' => 'Debe Permanecer',
        ]);
    }

    public function test_import_does_not_wipe_on_bad_rows(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => 'KEEP-2',
            'nombre_completo' => 'Dataset Intacta',
        ]);

        $path = $this->makeImportFile([
            ['', 'Sin ID', '2026-01-01', 'Curso X', '', 'Obligatoria'],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.formacion.formaciones'))
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'bad-rows.xlsx', null, null, true),
                'mode' => 'all',
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHasErrors('import_file');

        $this->assertSame(1, FormacionRegistro::query()->count());
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => 'KEEP-2',
            'nombre_completo' => 'Dataset Intacta',
        ]);
    }

    public function test_import_skips_empty_rows(): void
    {
        $path = $this->makeImportFile([
            ['2001', 'Solo Uno', '2026-01-10', 'Curso A', '5', 'Obligatoria'],
            ['', '', '', '', '', ''],
            ['2002', 'Dos', '2026-02-10', 'Curso B', '', 'Complementaria'],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'skip-empty.xlsx', null, null, true),
                'mode' => 'all',
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHas('status');

        $this->assertSame(2, FormacionRegistro::query()->count());
    }

    public function test_import_rejects_file_with_only_empty_rows(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => 'KEEP-EMPTY',
            'nombre_completo' => 'No Borrar',
        ]);

        $path = $this->makeImportFile([
            ['', '', '', '', '', ''],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.formacion.formaciones'))
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'empty-only.xlsx', null, null, true),
                'mode' => 'all',
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHasErrors('import_file');

        $this->assertSame(1, FormacionRegistro::query()->count());
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => 'KEEP-EMPTY',
            'nombre_completo' => 'No Borrar',
        ]);
    }

    public function test_import_rejects_when_max_rows_exceeded(): void
    {
        config(['formacion.import.max_rows' => 1]);

        FormacionRegistro::factory()->create([
            'numero_id' => 'KEEP-MAX',
            'nombre_completo' => 'Tope Filas',
        ]);

        $path = $this->makeImportFile([
            ['1', 'Uno', '2026-01-01', 'Curso A', '', 'Cat'],
            ['2', 'Dos', '2026-01-02', 'Curso B', '', 'Cat'],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.formacion.formaciones'))
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'too-many.xlsx', null, null, true),
                'mode' => 'all',
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHasErrors('import_file');

        $this->assertSame(1, FormacionRegistro::query()->count());
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => 'KEEP-MAX',
        ]);
    }

    public function test_import_accepts_xlsx_with_generic_mime_type(): void
    {
        $path = $this->makeImportFile([
            ['3001', 'Mime Genérico', '2026-03-01', 'Curso Mime', '', 'Obligatoria'],
        ]);

        $editor = $this->editorUser();

        // Hostinger/Windows a menudo reportan octet-stream; mimes:xlsx fallaría.
        $upload = new UploadedFile(
            $path,
            'formacion.xlsx',
            'application/octet-stream',
            null,
            true,
        );

        $this->actingAs($editor)
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => $upload,
                'mode' => 'all',
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHas('status')
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => '3001',
            'nombre_completo' => 'Mime Genérico',
        ]);
    }

    public function test_import_requires_edit_permission(): void
    {
        $viewer = $this->viewerUser();
        $path = $this->makeImportFile([
            ['1', 'Viewer Block', '2026-01-01', 'Curso', '', 'Cat'],
        ]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'blocked.xlsx', null, null, true),
                'mode' => 'all',
                'confirm_replace' => '1',
            ])
            ->assertForbidden();

        $this->assertSame(0, FormacionRegistro::query()->count());
    }

    public function test_formaciones_page_hides_import_for_view_only(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.formaciones'))
            ->assertOk()
            ->assertDontSee('formacion-import', false)
            ->assertDontSee('Plantilla e importar', false);
    }

    public function test_formaciones_page_shows_import_for_editor(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.formacion.formaciones'))
            ->assertOk()
            ->assertSee('formacion-import', false)
            ->assertSee("\$dispatch('open-modal', 'formacion-import')", false)
            ->assertSee('x-data=""', false)
            ->assertSee(route('gestion-humana.formacion.formaciones.import-template'), false)
            ->assertSee('Solo un mes', false)
            ->assertSee('Reemplazar todo el dataset', false);
    }

    public function test_import_period_keeps_other_months(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => 'SEP-1',
            'nombre_completo' => 'Septiembre Keep',
            'fecha_inicio' => '2026-09-15',
            'mes' => 9,
            'anio' => 2026,
        ]);
        FormacionRegistro::factory()->create([
            'numero_id' => 'OCT-OLD',
            'nombre_completo' => 'Octubre Viejo',
            'fecha_inicio' => '2026-10-05',
            'mes' => 10,
            'anio' => 2026,
        ]);

        $path = $this->makeImportFile([
            ['4001', 'Octubre Nuevo', '2026-10-12', 'Curso Oct', '8', 'Obligatoria'],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'octubre.xlsx', null, null, true),
                'mode' => 'period',
                'anio' => 2026,
                'mes' => 10,
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHas('status');

        $this->assertSame(2, FormacionRegistro::query()->count());
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => 'SEP-1',
            'nombre_completo' => 'Septiembre Keep',
        ]);
        $this->assertDatabaseMissing('formacion_registros', ['numero_id' => 'OCT-OLD']);
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => '4001',
            'mes' => 10,
            'anio' => 2026,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'formacion',
            'event_type' => 'import',
            'action' => 'import_replace_period',
        ]);
    }

    public function test_import_period_rejects_rows_outside_selected_month(): void
    {
        FormacionRegistro::factory()->create([
            'numero_id' => 'KEEP-MIX',
            'nombre_completo' => 'Debe Quedar',
            'fecha_inicio' => '2026-09-01',
            'mes' => 9,
            'anio' => 2026,
        ]);

        $path = $this->makeImportFile([
            ['5001', 'Octubre Ok', '2026-10-01', 'Curso Oct', '', 'Obligatoria'],
            ['5002', 'Noviembre Mal', '2026-11-01', 'Curso Nov', '', 'Obligatoria'],
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->from(route('gestion-humana.formacion.formaciones'))
            ->post(route('gestion-humana.formacion.formaciones.import'), [
                'import_file' => new UploadedFile($path, 'mixto.xlsx', null, null, true),
                'mode' => 'period',
                'anio' => 2026,
                'mes' => 10,
                'confirm_replace' => '1',
            ])
            ->assertRedirect(route('gestion-humana.formacion.formaciones'))
            ->assertSessionHasErrors('import_file');

        $this->assertSame(1, FormacionRegistro::query()->count());
        $this->assertDatabaseHas('formacion_registros', [
            'numero_id' => 'KEEP-MIX',
            'nombre_completo' => 'Debe Quedar',
        ]);
        $this->assertDatabaseMissing('formacion_registros', ['numero_id' => '5001']);
    }

    /**
     * @param  list<list<string|float|null>>  $rows
     */
    private function makeImportFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $labels = array_values(config('formacion.import.columns'));

        foreach ($labels as $i => $label) {
            $sheet->setCellValue([$i + 1, 1], $label);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'formacion-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
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

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.formacion',
            'formacion.edit',
        ]);

        return $user;
    }
}
