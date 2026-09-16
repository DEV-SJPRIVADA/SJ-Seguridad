<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CursosRegistrosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-15'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_vigencia_threshold_and_labels(): void
    {
        // umbral = 2026-09-15 - 335 = 2025-10-15
        $this->assertSame('2025-10-15', EmployeeCurso::vigenciaThreshold()->toDateString());

        $vigente = EmployeeCurso::factory()->make([
            'fecha_expedicion' => '2025-10-15',
        ]);
        $actualizar = EmployeeCurso::factory()->make([
            'fecha_expedicion' => '2025-10-14',
        ]);

        $this->assertSame('VIGENTE', $vigente->computeVigencia());
        $this->assertSame('ACTUALIZAR', $actualizar->computeVigencia());
    }

    public function test_lookup_returns_ficha_name(): void
    {
        EmployeeFichaProfile::query()->create([
            'document_number' => '1098765432',
            'full_name' => 'Ana Prueba',
        ]);

        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->getJson(route('gestion-humana.cursos.registros.lookup', ['cedula' => '1098765432']))
            ->assertOk()
            ->assertJson([
                'found' => true,
                'full_name' => 'Ana Prueba',
            ]);
    }

    public function test_store_update_and_unique_constraint(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.store'), [
                'document_number' => '111',
                'full_name' => 'Juan Perez',
                'curso_tipo_id' => $tipo->id,
                'fecha_expedicion' => '2026-01-01',
                'numero_curso' => 'NC-1',
                'estado' => 'SOLICITADO',
                'observaciones' => 'ok',
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'));

        $this->assertDatabaseHas('employee_cursos', [
            'document_number' => '111',
            'numero_curso' => 'NC-1',
            'full_name' => 'Juan Perez',
        ]);

        Storage::fake('local');
        $file = UploadedFile::fake()->create('alta.pdf', 50, 'application/pdf');

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.store'), [
                'document_number' => '222',
                'full_name' => 'Con Documento',
                'curso_tipo_id' => $tipo->id,
                'fecha_expedicion' => '2026-01-01',
                'numero_curso' => 'NC-DOC',
                'document' => $file,
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'));

        $withDoc = EmployeeCurso::query()
            ->where('document_number', '222')
            ->where('numero_curso', 'NC-DOC')
            ->firstOrFail();

        $this->assertTrue($withDoc->hasDocument());
        Storage::disk('local')->assertExists($withDoc->document_path);

        $this->actingAs($editor)
            ->from(route('gestion-humana.cursos.registros'))
            ->post(route('gestion-humana.cursos.registros.store'), [
                'document_number' => '111',
                'full_name' => 'Juan Perez',
                'curso_tipo_id' => $tipo->id,
                'fecha_expedicion' => '2026-02-01',
                'numero_curso' => 'NC-1',
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'))
            ->assertSessionHasErrors('numero_curso');

        $curso = EmployeeCurso::query()->firstOrFail();

        $this->actingAs($editor)
            ->patch(route('gestion-humana.cursos.registros.update', $curso), [
                'document_number' => '111',
                'full_name' => 'Juan Perez Actualizado',
                'curso_tipo_id' => $tipo->id,
                'fecha_expedicion' => '2026-03-01',
                'numero_curso' => 'NC-1',
                'estado' => 'ACTUALIZADO',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('employee_cursos', [
            'id' => $curso->id,
            'full_name' => 'Juan Perez Actualizado',
            'estado' => 'ACTUALIZADO',
        ]);
    }

    public function test_document_upload_download_and_destroy_with_record(): void
    {
        Storage::fake('local');

        $editor = $this->editorUser();
        $viewer = $this->viewerUser();
        $curso = EmployeeCurso::factory()->create();

        $file = UploadedFile::fake()->create('curso.pdf', 100, 'application/pdf');

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cursos.registros.document.upload', $curso), [
                'document' => $file,
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.document.upload', $curso), [
                'document' => $file,
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'));

        $curso->refresh();
        $this->assertTrue($curso->hasDocument());
        Storage::disk('local')->assertExists($curso->document_path);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros.document.download', $curso))
            ->assertOk();

        $path = $curso->document_path;

        $this->actingAs($editor)
            ->delete(route('gestion-humana.cursos.registros.destroy', $curso))
            ->assertRedirect(route('gestion-humana.cursos.registros'));

        $this->assertDatabaseMissing('employee_cursos', ['id' => $curso->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_viewer_can_export_but_not_mutate(): void
    {
        $viewer = $this->viewerUser();
        EmployeeCurso::factory()->create([
            'fecha_expedicion' => '2026-01-01',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros.export'))
            ->assertOk();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cursos.registros.store'), [
                'document_number' => '999',
                'full_name' => 'X',
                'curso_tipo_id' => CursoTipo::factory()->create()->id,
                'fecha_expedicion' => '2026-01-01',
                'numero_curso' => 'Z-1',
            ])
            ->assertForbidden();
    }

    public function test_export_respects_filters(): void
    {
        $viewer = $this->viewerUser();
        $tipoA = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);
        $tipoB = CursoTipo::factory()->create(['tipo_curso' => 'PRIMEROS AUXILIOS']);

        EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipoA->id,
            'document_number' => '111',
            'full_name' => 'Ana Filtrada',
            'numero_curso' => 'EXP-A',
            'fecha_expedicion' => '2026-01-01',
        ]);
        EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipoB->id,
            'document_number' => '222',
            'full_name' => 'Bruno Otro',
            'numero_curso' => 'EXP-B',
            'fecha_expedicion' => '2026-01-01',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros.export', [
                'document_number' => '111',
                'curso_tipo_id' => $tipoA->id,
            ]))
            ->assertOk();

        $temp = tempnam(sys_get_temp_dir(), 'cursos-export-');
        file_put_contents($temp, $response->streamedContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($temp));
        $sharedStrings = (string) $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if (is_file($temp)) {
            unlink($temp);
        }

        $this->assertStringContainsString('Ana Filtrada', $sharedStrings);
        $this->assertStringNotContainsString('Bruno Otro', $sharedStrings);
    }

    public function test_registros_page_includes_datatable_and_export_icon(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros', [
                'document_number' => '123',
                'solo_actualizar' => 1,
            ]))
            ->assertOk()
            ->assertSee('js-datatable', false)
            ->assertSee('data-dt-body-scroll="true"', false)
            ->assertSee('cursos-registros-page__export-icon', false)
            ->assertSee('document_number=123', false)
            ->assertSee('solo_actualizar=1', false)
            ->assertSee(route('gestion-humana.cursos.registros.export'), false);
    }

    public function test_filter_solo_actualizar(): void
    {
        $viewer = $this->viewerUser();
        $tipo = CursoTipo::factory()->create();

        EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
            'document_number' => 'A1',
            'fecha_expedicion' => '2025-10-14',
            'numero_curso' => 'OLD-1',
        ]);
        EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipo->id,
            'document_number' => 'B1',
            'fecha_expedicion' => '2025-10-15',
            'numero_curso' => 'NEW-1',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros', ['solo_actualizar' => 1]))
            ->assertOk()
            ->assertSee('A1', false)
            ->assertDontSee('B1', false);
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
