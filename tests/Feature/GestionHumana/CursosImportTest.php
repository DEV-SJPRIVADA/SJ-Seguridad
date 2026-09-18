<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CursosImportTest extends TestCase
{
    use RefreshDatabase;

    private CursoEscuela $escuelaSniper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();

        $this->escuelaSniper = CursoEscuela::factory()->create([
            'codigo' => '015',
            'nit' => '8050262894',
            'nombre' => 'SNIPER',
        ]);
    }

    public function test_import_template_requires_edit(): void
    {
        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros.import-template'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('gestion-humana.cursos.registros.import-template'))
            ->assertOk();
    }

    public function test_import_template_includes_numero_curso_anterior_column(): void
    {
        $editor = $this->editorUser();

        $response = $this->actingAs($editor)
            ->get(route('gestion-humana.cursos.registros.import-template'))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'cursos-tpl-');
        file_put_contents($temp, $response->streamedContent());

        $sheet = IOFactory::load($temp)->getActiveSheet();
        $keys = array_keys(config('cursos.import.columns'));
        foreach ($keys as $i => $key) {
            $this->assertSame(
                $key,
                (string) $sheet->getCell([$i + 1, 1])->getValue(),
            );
        }
        $this->assertContains('numero_curso_anterior', $keys);
        $this->assertNotContains('estado', $keys);

        if (is_file($temp)) {
            unlink($temp);
        }
    }

    public function test_import_inserts_updates_and_fails_unknown_tipo(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '100',
            'full_name' => 'Nombre Ficha Cien',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '200',
            'full_name' => 'Maria Ficha',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '300',
            'full_name' => 'Sin Tipo Ficha',
        ]);

        EmployeeCurso::factory()->create([
            'document_number' => '100',
            'full_name' => 'Viejo Nombre',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'ECSP0015-NC1',
            'fecha_expedicion' => '2024-01-01',
            'document_path' => 'employee-cursos/keep.pdf',
            'document_original_name' => 'keep.pdf',
        ]);

        $path = $this->makeImportFile([
            ['100', 'Nombre Del Excel Ignorado', 'ALTURAS', '2026-02-01', '', 'ECSP0015-NC1', 'upd'],
            ['200', 'Maria Nueva Excel', 'ALTURAS', '2026-03-01', '', 'ECSP0015-M256412', ''],
            ['300', 'Sin Tipo', 'INEXISTENTE', '2026-03-01', '', 'ECSP0015-NC3', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.import'), [
                'import_file' => new UploadedFile($path, 'cursos.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHas('status')
            ->assertSessionHas('import_failures');

        $updated = EmployeeCurso::query()
            ->where('document_number', '100')
            ->where('numero_curso', 'ECSP0015-NC1')
            ->firstOrFail();

        $this->assertSame('Nombre Ficha Cien', $updated->full_name);
        $this->assertSame('ACTUALIZADO', $updated->estado);
        $this->assertSame('employee-cursos/keep.pdf', $updated->document_path);
        $this->assertSame('keep.pdf', $updated->document_original_name);
        $this->assertSame($this->escuelaSniper->id, $updated->curso_escuela_id);
        $this->assertSame('015', $updated->escuela_codigo);
        $this->assertSame('8050262894', $updated->escuela_nit);
        $this->assertSame('SNIPER', $updated->escuela_nombre);

        $this->assertDatabaseHas('employee_cursos', [
            'document_number' => '200',
            'numero_curso' => 'ECSP0015-M256412',
            'full_name' => 'Maria Ficha',
            'estado' => 'ACTUALIZADO',
            'curso_escuela_id' => $this->escuelaSniper->id,
            'escuela_codigo' => '015',
            'escuela_nit' => '8050262894',
            'escuela_nombre' => 'SNIPER',
        ]);

        $this->assertDatabaseMissing('employee_cursos', [
            'document_number' => '300',
            'numero_curso' => 'ECSP0015-NC3',
        ]);
    }

    public function test_import_resolves_escuela_from_numero_curso_prefix(): void
    {
        $this->assertSame('15', CursoEscuela::extractCodigoFromNumeroCurso('ECSP0015-M256412'));
        $this->assertSame('15', CursoEscuela::normalizeCodigo('015'));
        $this->assertNull(CursoEscuela::extractCodigoFromNumeroCurso('SINCODIGO'));
    }

    public function test_import_fails_when_escuela_codigo_not_in_catalog(): void
    {
        $editor = $this->editorUser();
        CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);
        EmployeeFichaProfile::query()->create([
            'document_number' => '250',
            'full_name' => 'Sin Escuela',
        ]);

        $path = $this->makeImportFile([
            ['250', 'X', 'ALTURAS', '2026-03-01', '', 'ECSP9999-X1', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.import'), [
                'import_file' => new UploadedFile($path, 'cursos.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHas('import_failures');

        $this->assertStringContainsString(
            'No hay escuela activa en catalogo con codigo 9999',
            (string) (session('import_failures')[0]['reason'] ?? ''),
        );
    }

    public function test_import_rejects_cedula_not_in_ficha(): void
    {
        $editor = $this->editorUser();
        CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        $path = $this->makeImportFile([
            ['999', 'Persona Libre', 'ALTURAS', '2026-03-01', '', 'NC-9', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.import'), [
                'import_file' => new UploadedFile($path, 'cursos.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHas('import_failures');

        $this->assertDatabaseMissing('employee_cursos', [
            'document_number' => '999',
            'numero_curso' => 'NC-9',
        ]);

        $failures = session('import_failures');
        $this->assertIsArray($failures);
        $this->assertNotEmpty($failures);
        $this->assertStringContainsString(
            'La cedula no existe en Ficha empleados.',
            (string) ($failures[0]['reason'] ?? ''),
        );
    }

    public function test_import_renueva_por_numero_curso_anterior(): void
    {
        $editor = $this->editorUser();
        $tipoFund = CursoTipo::factory()->create(['tipo_curso' => 'FUNDAMENTACION']);
        $tipoReent = CursoTipo::factory()->create(['tipo_curso' => 'REENTRENAMIENTO']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '400',
            'full_name' => 'Pedro Renueva',
        ]);

        $curso = EmployeeCurso::factory()->create([
            'document_number' => '400',
            'full_name' => 'Pedro Renueva',
            'curso_tipo_id' => $tipoFund->id,
            'numero_curso' => 'OLD-100',
            'fecha_expedicion' => '2024-01-01',
            'estado' => EmployeeCurso::ESTADO_SOLICITADO,
            'document_path' => 'employee-cursos/old.pdf',
            'document_original_name' => 'old.pdf',
        ]);

        $path = $this->makeImportFile([
            ['400', 'Ignorado', 'REENTRENAMIENTO', '2026-05-01', 'OLD-100', 'ECSP0015-NEW200', 'renovado'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.import'), [
                'import_file' => new UploadedFile($path, 'cursos.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHas('status');

        $failures = session('import_failures');
        $this->assertTrue($failures === null || $failures === []);

        $curso->refresh();
        $this->assertSame('ECSP0015-NEW200', $curso->numero_curso);
        $this->assertSame($tipoReent->id, $curso->curso_tipo_id);
        $this->assertSame('2026-05-01', optional($curso->fecha_expedicion)?->format('Y-m-d'));
        $this->assertSame('ACTUALIZADO', $curso->estado);
        $this->assertSame($this->escuelaSniper->id, $curso->curso_escuela_id);
        $this->assertSame('employee-cursos/old.pdf', $curso->document_path);
        $this->assertSame(1, EmployeeCurso::query()->where('document_number', '400')->count());
        $this->assertDatabaseMissing('employee_cursos', [
            'document_number' => '400',
            'numero_curso' => 'OLD-100',
        ]);
    }

    public function test_import_fails_when_numero_curso_anterior_not_found(): void
    {
        $editor = $this->editorUser();
        CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '500',
            'full_name' => 'Sin Anterior',
        ]);

        $path = $this->makeImportFile([
            ['500', 'X', 'ALTURAS', '2026-05-01', 'NO-EXISTE', 'ECSP0015-NEW1', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.import'), [
                'import_file' => new UploadedFile($path, 'cursos.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHas('import_failures');

        $this->assertDatabaseMissing('employee_cursos', [
            'document_number' => '500',
            'numero_curso' => 'ECSP0015-NEW1',
        ]);

        $this->assertStringContainsString(
            'No se encontro el curso a renovar',
            (string) (session('import_failures')[0]['reason'] ?? ''),
        );
    }

    public function test_import_fails_when_new_numero_collides_with_other_row(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '600',
            'full_name' => 'Colision',
        ]);

        EmployeeCurso::factory()->create([
            'document_number' => '600',
            'full_name' => 'Colision',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'OLD-A',
            'fecha_expedicion' => '2024-01-01',
        ]);
        EmployeeCurso::factory()->create([
            'document_number' => '600',
            'full_name' => 'Colision',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'ECSP0015-TAKEN',
            'fecha_expedicion' => '2025-01-01',
        ]);

        $path = $this->makeImportFile([
            ['600', 'X', 'ALTURAS', '2026-05-01', 'OLD-A', 'ECSP0015-TAKEN', ''],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.import'), [
                'import_file' => new UploadedFile($path, 'cursos.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHas('import_failures');

        $this->assertDatabaseHas('employee_cursos', [
            'document_number' => '600',
            'numero_curso' => 'OLD-A',
        ]);
        $this->assertSame(2, EmployeeCurso::query()->where('document_number', '600')->count());
        $this->assertStringContainsString(
            'Ya existe otro curso con ese No.CURSO',
            (string) (session('import_failures')[0]['reason'] ?? ''),
        );
    }

    public function test_import_same_anterior_and_nuevo_updates_existing(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '700',
            'full_name' => 'Mismo Numero',
        ]);

        EmployeeCurso::factory()->create([
            'document_number' => '700',
            'full_name' => 'Mismo Numero',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'ECSP0015-SAME1',
            'fecha_expedicion' => '2024-01-01',
            'estado' => EmployeeCurso::ESTADO_PENDIENTE,
        ]);

        $path = $this->makeImportFile([
            ['700', 'X', 'ALTURAS', '2026-06-01', 'ECSP0015-SAME1', 'ECSP0015-SAME1', 'ok'],
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.import'), [
                'import_file' => new UploadedFile($path, 'cursos.xlsx', null, null, true),
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHas('status');

        $failures = session('import_failures');
        $this->assertTrue($failures === null || $failures === []);

        $row = EmployeeCurso::query()
            ->where('document_number', '700')
            ->where('numero_curso', 'ECSP0015-SAME1')
            ->firstOrFail();

        $this->assertSame('ACTUALIZADO', $row->estado);
        $this->assertSame('2026-06-01', optional($row->fecha_expedicion)?->format('Y-m-d'));
        $this->assertSame($this->escuelaSniper->id, $row->curso_escuela_id);
        $this->assertSame(1, EmployeeCurso::query()->where('document_number', '700')->count());
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function makeImportFile(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $keys = array_keys(config('cursos.import.columns'));
        $labels = array_values(config('cursos.import.columns'));

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

        $path = tempnam(sys_get_temp_dir(), 'cursos_import_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
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

    private function editorUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cursos',
            'cursos.view',
            'cursos.edit',
        ]);

        return $user;
    }
}
