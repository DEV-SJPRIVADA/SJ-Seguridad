<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeCursoPending;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Services\GestionHumana\EmployeeCursoImportService;
use App\Services\GestionHumana\EmployeeCursoPendingService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CursosNuevosSinCursoPendingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::parse('2026-09-21'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_migrate_leaves_queue_empty_even_with_activos_sin_curso(): void
    {
        EmployeeFichaProfile::query()->create([
            'document_number' => '1001',
            'full_name' => 'Activo Sin Curso',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->assertSame(0, app(EmployeeCursoPendingService::class)->countPendingActivos());
        $this->assertDatabaseCount('employee_curso_pending', 0);
    }

    public function test_enqueue_guards_reingreso_idempotencia_and_omitted(): void
    {
        $service = app(EmployeeCursoPendingService::class);

        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '2001',
            'full_name' => 'Nuevo Eligible',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $first = $service->enqueueIfEligible([
            'document_number' => '2001',
            'full_name' => 'Nuevo Eligible',
            'employee_ficha_profile_id' => $profile->id,
        ]);
        $this->assertNotNull($first);
        $this->assertSame(EmployeeCursoPending::STATUS_PENDING, $first->status);

        $second = $service->enqueueIfEligible([
            'document_number' => '2001',
            'employee_ficha_profile_id' => $profile->id,
        ]);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('employee_curso_pending', 1);

        EmployeeCurso::factory()->create([
            'document_number' => '3001',
            'full_name' => 'Con Historial',
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => '3001',
            'full_name' => 'Con Historial',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $this->assertNull($service->enqueueIfEligible([
            'document_number' => '3001',
            'full_name' => 'Con Historial',
        ]));

        $omittedProfile = EmployeeFichaProfile::query()->create([
            'document_number' => '4001',
            'full_name' => 'Omitido Antes',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        EmployeeCursoPending::factory()->omitted('n/a')->create([
            'document_number' => '4001',
            'full_name' => 'Omitido Antes',
            'employee_ficha_profile_id' => $omittedProfile->id,
        ]);

        $this->assertNull($service->enqueueIfEligible([
            'document_number' => '4001',
            'employee_ficha_profile_id' => $omittedProfile->id,
        ]));
    }

    public function test_count_and_list_only_pending_with_activo_profile(): void
    {
        $service = app(EmployeeCursoPendingService::class);

        $activo = EmployeeFichaProfile::query()->create([
            'document_number' => '5001',
            'full_name' => 'Activo Pending',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        $desvinculado = EmployeeFichaProfile::query()->create([
            'document_number' => '5002',
            'full_name' => 'Desvinculado Pending',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
        ]);

        EmployeeCursoPending::factory()->pending()->create([
            'document_number' => '5001',
            'full_name' => 'Activo Pending',
            'employee_ficha_profile_id' => $activo->id,
        ]);
        EmployeeCursoPending::factory()->pending()->create([
            'document_number' => '5002',
            'full_name' => 'Desvinculado Pending',
            'employee_ficha_profile_id' => $desvinculado->id,
        ]);
        EmployeeCursoPending::factory()->omitted()->create([
            'document_number' => '5003',
            'full_name' => 'Omitido',
        ]);

        $this->assertSame(1, $service->countPendingActivos());
        $this->assertCount(1, $service->listPendingActivos());
        $this->assertSame('5001', $service->listPendingActivos()->first()->document_number);
    }

    public function test_registros_icon_only_for_edit_and_cola_requires_edit(): void
    {
        $activo = EmployeeFichaProfile::query()->create([
            'document_number' => '6001',
            'full_name' => 'En Cola',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        EmployeeCursoPending::factory()->pending()->create([
            'document_number' => '6001',
            'full_name' => 'En Cola',
            'employee_ficha_profile_id' => $activo->id,
        ]);

        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros'))
            ->assertOk()
            ->assertDontSee('cursos-registros-page__nuevos-link', false)
            ->assertDontSee('Nuevos sin curso', false);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.registros', ['cola' => 'nuevos-sin-curso']))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('gestion-humana.cursos.registros'))
            ->assertOk()
            ->assertSee('cursos-registros-page__nuevos-link', false)
            ->assertSee('title="Nuevos sin curso"', false)
            ->assertSee('>1</span>', false);

        $this->actingAs($editor)
            ->get(route('gestion-humana.cursos.registros', ['cola' => 'nuevos-sin-curso']))
            ->assertOk()
            ->assertSee('En Cola', false)
            ->assertSee('6001', false)
            ->assertSee('Agregar curso', false)
            ->assertSee('Omitir', false);
    }

    public function test_omit_resolves_queue_and_viewer_forbidden(): void
    {
        $activo = EmployeeFichaProfile::query()->create([
            'document_number' => '7001',
            'full_name' => 'Para Omitir',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        $pending = EmployeeCursoPending::factory()->pending()->create([
            'document_number' => '7001',
            'full_name' => 'Para Omitir',
            'employee_ficha_profile_id' => $activo->id,
        ]);

        $viewer = $this->viewerUser();
        $editor = $this->editorUser();

        $this->actingAs($viewer)
            ->post(route('gestion-humana.cursos.registros.pendientes.omit', $pending), [
                'omit_reason' => 'No aplica',
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.pendientes.omit', $pending), [
                'omit_reason' => 'No aplica',
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros', ['cola' => 'nuevos-sin-curso']));

        $pending->refresh();
        $this->assertSame(EmployeeCursoPending::STATUS_OMITTED, $pending->status);
        $this->assertSame('No aplica', $pending->omit_reason);
        $this->assertSame(0, app(EmployeeCursoPendingService::class)->countPendingActivos());
    }

    public function test_store_resolves_pending_without_updating_ficha_profile(): void
    {
        $editor = $this->editorUser();
        $tipo = CursoTipo::factory()->create();
        $escuela = CursoEscuela::factory()->create([
            'codigo' => '015',
            'nit' => '8050262894',
            'nombre' => 'SNIPER',
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '8001',
            'full_name' => 'Nombre Ficha Original',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        $pending = EmployeeCursoPending::factory()->pending()->create([
            'document_number' => '8001',
            'full_name' => 'Nombre Ficha Original',
            'employee_ficha_profile_id' => $profile->id,
        ]);

        $this->actingAs($editor)
            ->post(route('gestion-humana.cursos.registros.store'), [
                'document_number' => '8001',
                'full_name' => 'Nombre Ficha Original',
                'curso_tipo_id' => $tipo->id,
                'curso_escuela_id' => $escuela->id,
                'fecha_expedicion' => '2026-01-01',
                'numero_curso' => 'ECSP015-T1',
                'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
            ])
            ->assertRedirect(route('gestion-humana.cursos.registros'));

        $pending->refresh();
        $this->assertSame(EmployeeCursoPending::STATUS_RESOLVED, $pending->status);
        $this->assertSame(EmployeeCursoPending::RESOLVED_VIA_FIRST_CURSO, $pending->resolved_via);
        $this->assertNotNull($pending->employee_curso_id);

        $profile->refresh();
        $this->assertSame('Nombre Ficha Original', $profile->full_name);
        $this->assertSame('8001', $profile->document_number);
    }

    public function test_import_insert_resolves_pending_update_does_not(): void
    {
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);
        $escuela = CursoEscuela::factory()->create([
            'codigo' => '015',
            'nit' => '900',
            'nombre' => 'Escuela Test',
            'is_active' => true,
        ]);

        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '9001',
            'full_name' => 'Import Resolve',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        $pending = EmployeeCursoPending::factory()->pending()->create([
            'document_number' => '9001',
            'full_name' => 'Import Resolve',
            'employee_ficha_profile_id' => $profile->id,
        ]);

        $editor = $this->editorUser();
        $path = $this->writeImportFile([
            ['9001', 'Import Resolve', 'ALTURAS', '2026-01-15', '', 'ECSP0015-M100', ''],
        ]);

        $stats = app(EmployeeCursoImportService::class)->import($path, $editor->id);
        $this->assertSame(1, $stats['imported']);

        $pending->refresh();
        $this->assertSame(EmployeeCursoPending::STATUS_RESOLVED, $pending->status);

        $anotherProfile = EmployeeFichaProfile::query()->create([
            'document_number' => '9002',
            'full_name' => 'Ya Tiene Curso',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        EmployeeCurso::factory()->withEscuela($escuela)->create([
            'document_number' => '9002',
            'full_name' => 'Ya Tiene Curso',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'ECSP0015-M200',
            'fecha_expedicion' => '2025-01-01',
            'employee_ficha_profile_id' => $anotherProfile->id,
        ]);
        $pendingUpdate = EmployeeCursoPending::factory()->pending()->create([
            'document_number' => '9002',
            'full_name' => 'Ya Tiene Curso',
            'employee_ficha_profile_id' => $anotherProfile->id,
        ]);

        // Update/renovación: no debe resolver (solo inserts). En la práctica no habría pending
        // si ya hay curso; forzamos fila pending para validar que update no la toca.
        $pathUpdate = $this->writeImportFile([
            ['9002', 'Ya Tiene Curso', 'ALTURAS', '2026-02-01', 'ECSP0015-M200', 'ECSP0015-M201', ''],
        ]);
        $updateStats = app(EmployeeCursoImportService::class)->import($pathUpdate, $editor->id);
        $this->assertSame(1, $updateStats['updated']);

        $pendingUpdate->refresh();
        $this->assertSame(EmployeeCursoPending::STATUS_PENDING, $pendingUpdate->status);
    }

    public function test_deleting_last_curso_does_not_reenqueue(): void
    {
        $service = app(EmployeeCursoPendingService::class);
        $editor = $this->editorUser();

        $profile = EmployeeFichaProfile::query()->create([
            'document_number' => '9101',
            'full_name' => 'Borrar Curso',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);
        $curso = EmployeeCurso::factory()->create([
            'document_number' => '9101',
            'full_name' => 'Borrar Curso',
            'employee_ficha_profile_id' => $profile->id,
        ]);
        EmployeeCursoPending::factory()->resolved()->create([
            'document_number' => '9101',
            'full_name' => 'Borrar Curso',
            'employee_ficha_profile_id' => $profile->id,
            'employee_curso_id' => $curso->id,
        ]);

        $this->actingAs($editor)
            ->delete(route('gestion-humana.cursos.registros.destroy', $curso))
            ->assertRedirect(route('gestion-humana.cursos.registros'));

        $this->assertNull($service->enqueueIfEligible([
            'document_number' => '9101',
            'employee_ficha_profile_id' => $profile->id,
        ]));
        $this->assertSame(0, $service->countPendingActivos());
    }

    public function test_nuevo_modal_marks_identity_readonly_from_ficha(): void
    {
        $editor = $this->editorUser();

        $this->actingAs($editor)
            ->get(route('gestion-humana.cursos.registros'))
            ->assertOk()
            ->assertSee('x-bind:readonly="identityLocked"', false)
            ->assertSee('La cédula y el nombre se toman de Ficha empleados', false);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function writeImportFile(array $rows): string
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

        $path = tempnam(sys_get_temp_dir(), 'cursos-pending-import-').'.xlsx';
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
