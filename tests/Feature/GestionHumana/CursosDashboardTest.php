<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
use App\Models\EmployeeFichaProfile;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursosDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_dashboard_requires_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cursos.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.cursos.dashboard.metrics'))
            ->assertForbidden();
    }

    public function test_dashboard_renders_and_metrics_respect_filters(): void
    {
        $viewer = $this->viewerUser();
        $tipoA = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);
        $tipoB = CursoTipo::factory()->create(['tipo_curso' => 'REENTRENAMIENTO']);

        $this->createCursoWithActiveFicha([
            'document_number' => '1001',
            'curso_tipo_id' => $tipoA->id,
            'fecha_expedicion' => now()->subDays(10)->toDateString(),
            'estado' => EmployeeCurso::ESTADO_SOLICITADO,
            'document_path' => null,
        ]);
        $this->createCursoWithActiveFicha([
            'document_number' => '1002',
            'curso_tipo_id' => $tipoB->id,
            'fecha_expedicion' => now()->subDays(400)->toDateString(),
            'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
            'document_path' => 'employee-cursos/x.pdf',
            'document_original_name' => 'x.pdf',
        ]);
        $this->createCursoWithActiveFicha([
            'document_number' => '1003',
            'curso_tipo_id' => $tipoA->id,
            'fecha_expedicion' => now()->subDays(350)->toDateString(),
            'estado' => EmployeeCurso::ESTADO_PENDIENTE,
            'document_path' => null,
        ]);
        $this->createCursoWithActiveFicha([
            'document_number' => '1004',
            'curso_tipo_id' => $tipoB->id,
            'fecha_expedicion' => now()->subDays(400)->toDateString(),
            'estado' => EmployeeCurso::ESTADO_SOLICITADO,
            'document_path' => null,
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('cursos-dashboard-page', false)
            ->assertSee('Total cursos', false)
            ->assertSee('Por actualizar / vencidos sin solicitar', false);

        $all = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cursos.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame(4, $all['kpis']['total']);
        $this->assertSame(1, $all['kpis']['actualizar']);
        $this->assertSame(1, $all['kpis']['vigentes']);
        $this->assertSame(2, $all['kpis']['vencidos']);
        $this->assertSame(3, $all['kpis']['sin_documento']);
        $this->assertSame(2, $all['kpis']['solicitados']);
        $this->assertSame(1, $all['kpis']['actualizados']);
        $this->assertSame(['VIGENTE', 'ACTUALIZAR', 'VENCIDO'], $all['charts']['by_vigencia']['labels']);
        $this->assertSame([1, 1, 2], $all['charts']['by_vigencia']['data']);
        $this->assertArrayHasKey('by_tipo', $all['charts']);
        $this->assertArrayHasKey('trend', $all['charts']);
        $this->assertCount(12, $all['charts']['trend']['nuevos']);

        $pendientesChart = $all['charts']['pendientes_renovacion_by_tipo'];
        $this->assertSame(['ALTURAS', 'REENTRENAMIENTO'], $pendientesChart['labels']);
        $this->assertSame([1, 0], $pendientesChart['actualizar']);
        $this->assertSame([0, 1], $pendientesChart['vencidos']);

        $filtered = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cursos.dashboard.metrics', [
                'curso_tipo_id' => $tipoA->id,
                'estado' => EmployeeCurso::ESTADO_SOLICITADO,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $filtered['kpis']['total']);
        $this->assertSame(1, $filtered['kpis']['solicitados']);
        $this->assertSame(0, $filtered['kpis']['actualizados']);
        $this->assertSame([], $filtered['charts']['pendientes_renovacion_by_tipo']['labels']);
        $this->assertSame([], $filtered['charts']['pendientes_renovacion_by_tipo']['actualizar']);
        $this->assertSame([], $filtered['charts']['pendientes_renovacion_by_tipo']['vencidos']);
    }

    public function test_dashboard_metrics_exclude_desvinculados_and_without_ficha(): void
    {
        $viewer = $this->viewerUser();
        $tipo = CursoTipo::factory()->create(['tipo_curso' => 'ALTURAS']);

        $this->createCursoWithActiveFicha([
            'document_number' => '2001',
            'full_name' => 'Activo',
            'curso_tipo_id' => $tipo->id,
            'fecha_expedicion' => now()->toDateString(),
            'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
        ]);

        $desvinculado = EmployeeCurso::factory()->create([
            'document_number' => '2002',
            'full_name' => 'Desvinculado',
            'curso_tipo_id' => $tipo->id,
            'fecha_expedicion' => now()->toDateString(),
            'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
        ]);
        EmployeeFichaProfile::query()->create([
            'document_number' => $desvinculado->document_number,
            'full_name' => $desvinculado->full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
        ]);

        EmployeeCurso::factory()->create([
            'document_number' => '2003',
            'full_name' => 'Sin Ficha',
            'curso_tipo_id' => $tipo->id,
            'fecha_expedicion' => now()->toDateString(),
            'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
        ]);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cursos.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame(1, $metrics['kpis']['total']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCursoWithActiveFicha(array $attributes): EmployeeCurso
    {
        $curso = EmployeeCurso::factory()->create($attributes);

        EmployeeFichaProfile::query()->create([
            'document_number' => $curso->document_number,
            'full_name' => $curso->full_name,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        return $curso;
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
}
