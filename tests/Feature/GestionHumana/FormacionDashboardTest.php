<?php

namespace Tests\Feature\GestionHumana;

use App\Models\FormacionRegistro;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormacionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_dashboard_and_metrics_require_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.formacion.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics'))
            ->assertForbidden();
    }

    public function test_dashboard_renders_kpis_and_metrics_payload(): void
    {
        $viewer = $this->viewerUser();
        $currentYear = (int) now()->year;

        FormacionRegistro::factory()->create([
            'anio' => $currentYear,
            'mes' => 1,
            'categoria' => 'Obligatoria',
            'fecha_inicio' => sprintf('%d-01-15', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear,
            'mes' => 1,
            'categoria' => 'Obligatoria',
            'fecha_inicio' => sprintf('%d-01-20', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear,
            'mes' => 6,
            'categoria' => 'Complementaria',
            'fecha_inicio' => sprintf('%d-06-10', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear - 1,
            'mes' => 3,
            'categoria' => 'Recertificación',
            'fecha_inicio' => sprintf('%d-03-01', $currentYear - 1),
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.dashboard'))
            ->assertOk()
            ->assertSee('Total formaciones', false)
            ->assertSee('Distribución por mes', false)
            ->assertSee('Distribución por categoría', false)
            ->assertSee('cursos-dashboard-page', false)
            ->assertSee('formacion-chart-mes', false);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame($currentYear, $metrics['anio']);
        $this->assertSame(3, $metrics['total']);
        $this->assertCount(12, $metrics['por_mes']);
        $this->assertSame(2, $metrics['por_mes'][1]);
        $this->assertSame(1, $metrics['por_mes'][6]);
        $this->assertSame(0, $metrics['por_mes'][2]);
        $this->assertSame(
            [
                ['categoria' => 'Obligatoria', 'total' => 2],
                ['categoria' => 'Complementaria', 'total' => 1],
            ],
            $metrics['por_categoria']
        );
        $this->assertSame(['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'], $metrics['charts']['por_mes']['labels']);
        $this->assertSame([2, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0], $metrics['charts']['por_mes']['data']);
        $this->assertSame(['Obligatoria', 'Complementaria'], $metrics['charts']['por_categoria']['labels']);
        $this->assertSame([2, 1], $metrics['charts']['por_categoria']['data']);
    }

    public function test_metrics_filter_by_anio(): void
    {
        $viewer = $this->viewerUser();
        $currentYear = (int) now()->year;
        $previousYear = $currentYear - 1;

        FormacionRegistro::factory()->count(2)->create([
            'anio' => $currentYear,
            'mes' => 2,
            'categoria' => 'A',
            'fecha_inicio' => sprintf('%d-02-01', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $previousYear,
            'mes' => 4,
            'categoria' => 'B',
            'fecha_inicio' => sprintf('%d-04-01', $previousYear),
        ]);

        $filtered = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', [
                'anio' => $previousYear,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame($previousYear, $filtered['anio']);
        $this->assertSame(1, $filtered['total']);
        $this->assertSame(1, $filtered['por_mes'][4]);
        $this->assertSame(
            [['categoria' => 'B', 'total' => 1]],
            $filtered['por_categoria']
        );
    }

    public function test_default_anio_falls_back_to_latest_with_data(): void
    {
        $viewer = $this->viewerUser();
        $pastYear = (int) now()->year - 2;

        FormacionRegistro::factory()->create([
            'anio' => $pastYear,
            'mes' => 8,
            'categoria' => 'Histórica',
            'fecha_inicio' => sprintf('%d-08-01', $pastYear),
        ]);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame($pastYear, $metrics['anio']);
        $this->assertSame(1, $metrics['total']);
        $this->assertSame(1, $metrics['por_mes'][8]);
    }

    public function test_por_categoria_respects_top_n(): void
    {
        config(['formacion.dashboard.categoria_top' => 2]);

        $viewer = $this->viewerUser();
        $year = (int) now()->year;

        foreach (['Cat A' => 5, 'Cat B' => 3, 'Cat C' => 1] as $categoria => $count) {
            FormacionRegistro::factory()->count($count)->create([
                'anio' => $year,
                'mes' => 1,
                'categoria' => $categoria,
                'fecha_inicio' => sprintf('%d-01-01', $year),
            ]);
        }

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', ['anio' => $year]))
            ->assertOk()
            ->json();

        $this->assertSame(9, $metrics['total']);
        $this->assertCount(2, $metrics['por_categoria']);
        $this->assertSame('Cat A', $metrics['por_categoria'][0]['categoria']);
        $this->assertSame(5, $metrics['por_categoria'][0]['total']);
        $this->assertSame('Cat B', $metrics['por_categoria'][1]['categoria']);
        $this->assertSame(3, $metrics['por_categoria'][1]['total']);
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
}
