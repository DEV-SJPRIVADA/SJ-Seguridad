<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CursoTipo;
use App\Models\EmployeeCurso;
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

        EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipoA->id,
            'fecha_expedicion' => now()->subDays(10)->toDateString(),
            'estado' => EmployeeCurso::ESTADO_SOLICITADO,
            'document_path' => null,
        ]);
        EmployeeCurso::factory()->create([
            'curso_tipo_id' => $tipoB->id,
            'fecha_expedicion' => now()->subDays(400)->toDateString(),
            'estado' => EmployeeCurso::ESTADO_ACTUALIZADO,
            'document_path' => 'employee-cursos/x.pdf',
            'document_original_name' => 'x.pdf',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cursos.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('cursos-dashboard-page', false)
            ->assertSee('Total cursos', false);

        $all = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cursos.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame(2, $all['kpis']['total']);
        $this->assertSame(0, $all['kpis']['actualizar']);
        $this->assertSame(1, $all['kpis']['vigentes']);
        $this->assertSame(1, $all['kpis']['vencidos']);
        $this->assertSame(1, $all['kpis']['sin_documento']);
        $this->assertSame(1, $all['kpis']['solicitados']);
        $this->assertSame(1, $all['kpis']['actualizados']);
        $this->assertSame(['VIGENTE', 'ACTUALIZAR', 'VENCIDO'], $all['charts']['by_vigencia']['labels']);
        $this->assertSame([1, 0, 1], $all['charts']['by_vigencia']['data']);
        $this->assertArrayHasKey('by_tipo', $all['charts']);
        $this->assertArrayHasKey('trend', $all['charts']);
        $this->assertCount(12, $all['charts']['trend']['nuevos']);

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
