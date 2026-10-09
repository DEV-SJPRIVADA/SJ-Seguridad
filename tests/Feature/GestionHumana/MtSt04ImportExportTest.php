<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\MtSt04Registro;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MtSt04ImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-10-09', 'America/Bogota')->startOfDay());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_import_template_requires_edit(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.matriz.import-template'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('gestion-humana.mt-st-04.matriz.import-template'))
            ->assertOk();
    }

    public function test_import_template_has_operative_headers_without_estado_or_ficha_columns(): void
    {
        $editor = $this->editorUser();

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.mt-st-04.matriz.import-template'))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'mt04-tpl-');
        file_put_contents($temp, $response->streamedContent());

        $sheet = IOFactory::load($temp)->getActiveSheet();
        $keys = array_keys(config('mt_st_04.import.columns'));
        $labels = array_values(config('mt_st_04.import.columns'));

        foreach ($keys as $i => $key) {
            $this->assertSame($key, (string) $sheet->getCell([$i + 1, 1])->getValue());
            $this->assertSame($labels[$i], (string) $sheet->getCell([$i + 1, 2])->getValue());
        }

        $this->assertContains('document_number', $keys);
        $this->assertContains('arma', $keys);
        $this->assertContains('fecha_examen_1', $keys);
        $this->assertContains('apto', $keys);
        $this->assertContains('fecha_examen_2', $keys);
        $this->assertNotContains('estado_1', $keys);
        $this->assertNotContains('estado_2', $keys);
        $this->assertNotContains('fecha_vencimiento_1', $keys);
        $this->assertNotContains('cargo', $keys);
        $this->assertNotContains('ciudad', $keys);
        $this->assertNotContains('puesto', $keys);

        if (is_file($temp)) {
            unlink($temp);
        }
    }

    public function test_import_creates_and_updates_by_document_number(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR');
        $this->createFicha('1002003002', 'Luis Nuevo', 'SUPERVISOR');

        MtSt04Registro::factory()->create([
            'document_number' => '1002003001',
            'arma' => 'NO',
            'apto' => 'NO',
            'observaciones_1' => 'antes',
        ]);

        $path = $this->makeImportFile([
            // CEDULA, NOMBRE, ARMA, FECHA EXAMEN1, APTO, OBS1, FECHA EXAMEN2, OBS2
            ['1002003001', 'Ignorado', 'SI', '2026-01-01', 'SI', 'actualizado', '2026-02-01', 'vial'],
            ['1002003002', 'Ignorado', 'NO', '2026-03-01', 'NO', 'nuevo', '', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.matriz.import'), [
                'import_file' => new UploadedFile($path, 'mt_st_04.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.mt-st-04.matriz'))
            ->assertSessionHas('status');

        $updated = MtSt04Registro::query()->where('document_number', '1002003001')->firstOrFail();
        $this->assertSame('SI', $updated->arma);
        $this->assertSame('SI', $updated->apto);
        $this->assertSame('actualizado', $updated->observaciones_1);
        $this->assertSame('2026-12-31', $updated->fecha_vencimiento_1?->toDateString());
        $this->assertSame('2027-01-31', $updated->fecha_vencimiento_2?->toDateString());
        $this->assertSame(MtSt04Registro::ESTADO_VIGENTE, $updated->estado_1);
        $this->assertSame(MtSt04Registro::ESTADO_VIGENTE, $updated->estado_2);

        $created = MtSt04Registro::query()->where('document_number', '1002003002')->firstOrFail();
        $this->assertSame('NO', $created->arma);
        $this->assertSame('nuevo', $created->observaciones_1);
        $this->assertSame('2027-02-28', $created->fecha_vencimiento_1?->toDateString());
        $this->assertNull($created->fecha_examen_2);
        $this->assertNull($created->fecha_vencimiento_2);

        $this->assertSame(2, MtSt04Registro::query()->count());
    }

    public function test_import_rejects_orphan_cedula_and_keeps_uniqueness(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR');

        $path = $this->makeImportFile([
            ['9999999999', 'Sin Ficha', 'SI', '2026-01-01', 'SI', 'huérfana', '', ''],
            ['1002003001', 'Ana', 'SI', '2026-01-01', 'SI', 'ok', '', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.matriz.import'), [
                'import_file' => new UploadedFile($path, 'mt_st_04.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.mt-st-04.matriz'))
            ->assertSessionHas('status')
            ->assertSessionHas('import_failures');

        $failures = session('import_failures');
        $this->assertCount(1, $failures);
        $this->assertStringContainsString(
            'La cédula no existe en Ficha empleados.',
            (string) ($failures[0]['reason'] ?? ''),
        );

        $this->assertDatabaseMissing('mt_st_04_registros', ['document_number' => '9999999999']);
        $this->assertSame(1, MtSt04Registro::query()->where('document_number', '1002003001')->count());
    }

    public function test_import_last_duplicate_row_wins(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR');

        $path = $this->makeImportFile([
            ['1002003001', 'A', 'NO', '2026-01-01', 'NO', 'primera', '', ''],
            ['1002003001', 'A', 'SI', '2026-02-01', 'SI', 'última', '', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.matriz.import'), [
                'import_file' => new UploadedFile($path, 'mt_st_04.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.mt-st-04.matriz'))
            ->assertSessionHas('status');

        $registro = MtSt04Registro::query()->where('document_number', '1002003001')->firstOrFail();
        $this->assertSame(1, MtSt04Registro::query()->count());
        $this->assertSame('SI', $registro->arma);
        $this->assertSame('última', $registro->observaciones_1);
        $this->assertSame('2026-02-01', $registro->fecha_examen_1?->toDateString());
        $this->assertStringContainsString('duplicada', (string) session('status'));
    }

    public function test_import_estado2_no_aplica_from_live_cargo(): void
    {
        $editor = $this->editorUser();
        $this->createFicha('1002003001', 'Guarda Uno', 'GUARDA');

        $path = $this->makeImportFile([
            ['1002003001', 'Ignorado', 'SI', '2026-01-01', 'SI', '', '2026-01-01', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.mt-st-04.matriz.import'), [
                'import_file' => new UploadedFile($path, 'mt_st_04.xlsx', null, null, true),
            ])
            ->assertRedirect();

        $registro = MtSt04Registro::query()->where('document_number', '1002003001')->firstOrFail();
        $this->assertSame(MtSt04Registro::ESTADO_NO_APLICA, $registro->estado_2);
        $this->assertSame(MtSt04Registro::ESTADO_VIGENTE, $registro->estado_1);
    }

    public function test_viewer_forbidden_on_import(): void
    {
        $viewer = $this->viewerUser();
        $this->createFicha('1002003001', 'Ana Activa');

        $path = $this->makeImportFile([
            ['1002003001', 'A', 'SI', '2026-01-01', 'SI', '', '', ''],
        ]);

        $this->actingAs($viewer)
            ->post(route('gestion-humana.mt-st-04.matriz.import'), [
                'import_file' => new UploadedFile($path, 'mt_st_04.xlsx', null, null, true),
            ])
            ->assertForbidden();
    }

    public function test_viewer_can_export_and_editor_export_ok(): void
    {
        $viewer = $this->viewerUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR', EmployeeFichaProfile::STATUS_ACTIVO, [
            'work_city_name' => 'Bogotá',
            'cost_center_name' => 'CC-01',
        ]);
        MtSt04Registro::factory()->create([
            'document_number' => '1002003001',
            'arma' => 'SI',
            'fecha_examen_1' => '2026-01-01',
            'fecha_vencimiento_1' => '2026-12-31',
            'estado_1' => MtSt04Registro::ESTADO_VIGENTE,
            'apto' => 'SI',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.matriz.export'))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'mt04-exp-');
        file_put_contents($temp, $response->streamedContent());

        $sheet = IOFactory::load($temp)->getActiveSheet();
        $this->assertSame('MT-ST-04 Matriz — '.config('app.name'), (string) $sheet->getCell([1, 1])->getValue());
        $this->assertSame('CEDULA', (string) $sheet->getCell([1, 2])->getValue());
        $this->assertSame('1002003001', (string) $sheet->getCell([1, 3])->getValue());
        $this->assertSame('Ana Activa', (string) $sheet->getCell([2, 3])->getValue());
        $this->assertSame('SUPERVISOR', (string) $sheet->getCell([3, 3])->getValue());
        $this->assertSame('Bogotá', (string) $sheet->getCell([4, 3])->getValue());
        $this->assertSame('CC-01', (string) $sheet->getCell([5, 3])->getValue());
        $this->assertSame('SI', (string) $sheet->getCell([6, 3])->getValue());
        $this->assertSame('VIGENTE', (string) $sheet->getCell([11, 3])->getValue());

        if (is_file($temp)) {
            unlink($temp);
        }
    }

    public function test_export_respects_ficha_activo_filter(): void
    {
        $viewer = $this->viewerUser();
        $this->createFicha('1002003001', 'Ana Activa', 'SUPERVISOR', EmployeeFichaProfile::STATUS_ACTIVO);
        $this->createFicha('1002003002', 'Luis Retiro', 'SUPERVISOR', EmployeeFichaProfile::STATUS_DESVINCULADO);
        MtSt04Registro::factory()->create(['document_number' => '1002003001', 'arma' => 'SI']);
        MtSt04Registro::factory()->create(['document_number' => '1002003002', 'arma' => 'NO']);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.mt-st-04.matriz.export'))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'mt04-exp2-');
        file_put_contents($temp, $response->streamedContent());
        $sheet = IOFactory::load($temp)->getActiveSheet();

        $this->assertSame('1002003001', (string) $sheet->getCell([1, 3])->getValue());
        $this->assertSame('', (string) $sheet->getCell([1, 4])->getValue());

        if (is_file($temp)) {
            unlink($temp);
        }
    }

    /**
     * @param  list<list<string|null>>  $rows
     */
    private function makeImportFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $keys = array_keys(config('mt_st_04.import.columns'));
        $labels = array_values(config('mt_st_04.import.columns'));

        foreach ($keys as $i => $key) {
            $col = $i + 1;
            $sheet->setCellValue([$col, 1], $key);
            $sheet->setCellValue([$col, 2], $labels[$i]);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 3], $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'mt04_import_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createFicha(
        string $documentNumber,
        string $fullName,
        string $positionName = 'SUPERVISOR',
        string $employmentStatus = EmployeeFichaProfile::STATUS_ACTIVO,
        array $extra = [],
    ): EmployeeFichaProfile {
        return EmployeeFichaProfile::query()->create(array_merge([
            'document_number' => $documentNumber,
            'full_name' => $fullName,
            'position_name' => $positionName,
            'employment_status' => $employmentStatus,
        ], $extra));
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.mt_st_04',
            'mt_st_04.view',
        ]);

        return $user;
    }

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.mt_st_04',
            'mt_st_04.view',
            'mt_st_04.edit',
        ]);

        return $user;
    }
}
