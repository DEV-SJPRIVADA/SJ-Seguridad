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
            'nombre_curso' => 'Curso A',
            'calificacion' => '9',
            'fecha_inicio' => sprintf('%d-01-15', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear,
            'mes' => 1,
            'categoria' => 'Obligatoria',
            'nombre_curso' => 'Curso A',
            'calificacion' => '6',
            'fecha_inicio' => sprintf('%d-01-20', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear,
            'mes' => 6,
            'categoria' => 'Complementaria',
            'nombre_curso' => 'Curso B',
            'calificacion' => null,
            'fecha_inicio' => sprintf('%d-06-10', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear - 1,
            'mes' => 3,
            'categoria' => 'Recertificación',
            'nombre_curso' => 'Curso C',
            'fecha_inicio' => sprintf('%d-03-01', $currentYear - 1),
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.formacion.dashboard'))
            ->assertOk()
            ->assertSee('Total', false)
            ->assertSee('Aprobados', false)
            ->assertSee('Reprobados', false)
            ->assertSee('No realizadas', false)
            ->assertSee('dash_mes', false)
            ->assertSee('dash_estado', false)
            ->assertSee('dash_nombre_curso', false)
            ->assertSee('Distribución por mes', false)
            ->assertSee('Por estado', false)
            ->assertSee('Top categorías', false)
            ->assertSee('formacion-chart-mes', false)
            ->assertSee('formacion-chart-estado', false);

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
        $this->assertSame(1, $metrics['por_estado']['aprobado']);
        $this->assertSame(1, $metrics['por_estado']['reprobado']);
        $this->assertSame(1, $metrics['por_estado']['no_realizada']);
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
        $this->assertSame(['Aprobado', 'Reprobado', 'No realizada'], $metrics['charts']['por_estado']['labels']);
        $this->assertSame([1, 1, 1], $metrics['charts']['por_estado']['data']);
    }

    public function test_metrics_filter_by_anio_mes_estado_and_curso(): void
    {
        $viewer = $this->viewerUser();
        $currentYear = (int) now()->year;
        $previousYear = $currentYear - 1;

        FormacionRegistro::factory()->count(2)->create([
            'anio' => $currentYear,
            'mes' => 2,
            'categoria' => 'A',
            'nombre_curso' => 'Altura',
            'calificacion' => '8',
            'fecha_inicio' => sprintf('%d-02-01', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear,
            'mes' => 2,
            'categoria' => 'A',
            'nombre_curso' => 'Altura',
            'calificacion' => '5',
            'fecha_inicio' => sprintf('%d-02-05', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear,
            'mes' => 3,
            'categoria' => 'A',
            'nombre_curso' => 'Altura',
            'calificacion' => '9',
            'fecha_inicio' => sprintf('%d-03-01', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $currentYear,
            'mes' => 2,
            'categoria' => 'A',
            'nombre_curso' => 'Defensivo',
            'calificacion' => '9',
            'fecha_inicio' => sprintf('%d-02-10', $currentYear),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $previousYear,
            'mes' => 4,
            'categoria' => 'B',
            'nombre_curso' => 'Altura',
            'calificacion' => '9',
            'fecha_inicio' => sprintf('%d-04-01', $previousYear),
        ]);

        $byAnio = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', [
                'anio' => $previousYear,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame($previousYear, $byAnio['anio']);
        $this->assertSame(1, $byAnio['total']);
        $this->assertSame(1, $byAnio['por_mes'][4]);
        $this->assertSame(
            [['categoria' => 'B', 'total' => 1]],
            $byAnio['por_categoria']
        );

        $byMes = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', [
                'anio' => $currentYear,
                'mes' => 2,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(4, $byMes['total']);
        $this->assertSame(4, $byMes['por_mes'][2]);
        $this->assertSame(0, $byMes['por_mes'][3]);

        $byEstado = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', [
                'anio' => $currentYear,
                'mes' => 2,
                'estado' => 'aprobado',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(3, $byEstado['total']);
        $this->assertSame(3, $byEstado['por_estado']['aprobado']);
        $this->assertSame(0, $byEstado['por_estado']['reprobado']);

        $byCurso = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', [
                'anio' => $currentYear,
                'mes' => 2,
                'estado' => 'aprobado',
                'nombre_curso' => 'Altura',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(2, $byCurso['total']);
        $this->assertSame('Altura', $byCurso['filters']['nombre_curso']);
    }

    public function test_metrics_curso_options_depend_on_mes(): void
    {
        $viewer = $this->viewerUser();
        $year = (int) now()->year;

        FormacionRegistro::factory()->create([
            'anio' => $year,
            'mes' => 1,
            'nombre_curso' => 'Solo Enero',
            'categoria' => 'A',
            'fecha_inicio' => sprintf('%d-01-01', $year),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $year,
            'mes' => 2,
            'nombre_curso' => 'Solo Febrero',
            'categoria' => 'A',
            'fecha_inicio' => sprintf('%d-02-01', $year),
        ]);
        FormacionRegistro::factory()->create([
            'anio' => $year,
            'mes' => 2,
            'nombre_curso' => 'Compartido',
            'categoria' => 'A',
            'fecha_inicio' => sprintf('%d-02-15', $year),
        ]);

        $allMonths = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', ['anio' => $year]))
            ->assertOk()
            ->json('options.cursos');

        $allLabels = collect($allMonths)->pluck('value')->all();
        $this->assertContains('Solo Enero', $allLabels);
        $this->assertContains('Solo Febrero', $allLabels);
        $this->assertContains('Compartido', $allLabels);

        $febrero = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', [
                'anio' => $year,
                'mes' => 2,
            ]))
            ->assertOk()
            ->json('options.cursos');

        $febreroLabels = collect($febrero)->pluck('value')->all();
        $this->assertContains('Solo Febrero', $febreroLabels);
        $this->assertContains('Compartido', $febreroLabels);
        $this->assertNotContains('Solo Enero', $febreroLabels);

        $clearedCurso = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.formacion.dashboard.metrics', [
                'anio' => $year,
                'mes' => 2,
                'nombre_curso' => 'Solo Enero',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame('', $clearedCurso['filters']['nombre_curso']);
        $this->assertSame(2, $clearedCurso['total']);
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
