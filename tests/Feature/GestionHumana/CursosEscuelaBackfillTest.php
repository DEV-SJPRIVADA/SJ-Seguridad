<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoEscuela;
use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Services\GestionHumana\EmployeeCursoEscuelaBackfillService;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursosEscuelaBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_backfill_fills_escuela_snapshot_from_numero_curso(): void
    {
        $escuela = CursoEscuela::factory()->create([
            'codigo' => '981',
            'nit' => '8300393700',
            'nombre' => 'ECOLVIP',
            'is_active' => true,
        ]);
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        EmployeeFichaProfile::query()->create([
            'document_number' => '555',
            'full_name' => 'Backfill Test',
        ]);

        $curso = EmployeeCurso::factory()->create([
            'document_number' => '555',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'ECSP0981-N108984',
            'curso_escuela_id' => null,
            'escuela_codigo' => null,
            'escuela_nit' => null,
            'escuela_nombre' => null,
        ]);

        $stats = app(EmployeeCursoEscuelaBackfillService::class)->backfill();

        $this->assertSame(1, $stats['updated']);

        $curso->refresh();
        $this->assertSame($escuela->id, $curso->curso_escuela_id);
        $this->assertSame('981', $curso->escuela_codigo);
        $this->assertSame('8300393700', $curso->escuela_nit);
        $this->assertSame('ECOLVIP', $curso->escuela_nombre);
    }

    public function test_datatable_shows_escuela_resolved_from_numero_curso_when_snapshot_empty(): void
    {
        $editor = User::factory()->create(['must_change_password' => false]);
        $editor->givePermissionTo([
            'view.board.gestion_humana.cursos',
            'cursos.view',
            'cursos.edit',
        ]);

        CursoEscuela::factory()->create([
            'codigo' => '1195',
            'nit' => '8190036527',
            'nombre' => 'ESCOLVIG',
            'is_active' => true,
        ]);
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        EmployeeCurso::factory()->create([
            'document_number' => '777',
            'full_name' => 'Sin Snapshot',
            'curso_tipo_id' => $tipo->id,
            'numero_curso' => 'ECSP1195-M537676',
            'curso_escuela_id' => null,
            'escuela_codigo' => null,
            'escuela_nit' => null,
            'escuela_nombre' => null,
        ]);

        $json = $this->actingAs($editor)
            ->getJson(route('gestion-humana.cursos.registros.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
                'document_number' => '777',
            ]))
            ->assertOk()
            ->json();

        $this->assertGreaterThanOrEqual(1, $json['recordsFiltered']);
        $rowHtml = implode(' ', $json['data'][0] ?? []);
        $this->assertStringContainsString('ESCOLVIG', $rowHtml);
        $this->assertStringContainsString('1195', $rowHtml);
        $this->assertStringContainsString('8190036527', $rowHtml);
    }
}
