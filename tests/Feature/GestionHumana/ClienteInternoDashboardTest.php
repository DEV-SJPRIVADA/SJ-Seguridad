<?php

namespace Tests\Feature\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoSolicitud;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteInternoDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_dashboard_and_metrics_require_dashboard_access(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.cliente-interno.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.cliente-interno.dashboard.metrics'))
            ->assertForbidden();
    }

    public function test_dashboard_allows_view_or_parameters_permission(): void
    {
        $viewer = $this->viewerUser();
        $catalogOnly = $this->catalogUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.cliente-interno.dashboard'))
            ->assertOk()
            ->assertSee('Total solicitudes', false)
            ->assertSee('Por estado', false)
            ->assertSee('Tendencia mensual', false)
            ->assertSee('Distribución días de respuesta', false)
            ->assertSee('dash_anio', false)
            ->assertSee('dash_mes', false)
            ->assertSee('cliente-interno-chart-tendencia', false)
            ->assertSee('cliente-interno-chart-estado', false)
            ->assertSee('cliente-interno-chart-dias', false);

        $this->actingAs($catalogOnly)
            ->get(route('gestion-humana.cliente-interno.dashboard'))
            ->assertOk();

        $this->actingAs($catalogOnly)
            ->getJson(route('gestion-humana.cliente-interno.dashboard.metrics'))
            ->assertOk();
    }

    public function test_dashboard_renders_kpis_and_metrics_payload(): void
    {
        $viewer = $this->viewerUser();
        $currentYear = (int) now()->year;
        $pendiente = ClienteInternoEstado::query()->where('code', 'PENDIENTE')->firstOrFail();
        $respondida = ClienteInternoEstado::query()->where('code', 'RESPONDIDA')->firstOrFail();

        ClienteInternoSolicitud::factory()->create([
            'anio' => $currentYear,
            'mes' => 1,
            'fecha_solicitud' => sprintf('%d-01-10', $currentYear),
            'estado_id' => $pendiente->id,
            'dias_respuesta' => 1,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'anio' => $currentYear,
            'mes' => 1,
            'fecha_solicitud' => sprintf('%d-01-15', $currentYear),
            'estado_id' => $respondida->id,
            'dias_respuesta' => 4,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'anio' => $currentYear,
            'mes' => 6,
            'fecha_solicitud' => sprintf('%d-06-10', $currentYear),
            'estado_id' => null,
            'dias_respuesta' => 12,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'anio' => $currentYear,
            'mes' => 6,
            'fecha_solicitud' => sprintf('%d-06-20', $currentYear),
            'estado_id' => $pendiente->id,
            'dias_respuesta' => null,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'anio' => $currentYear - 1,
            'mes' => 3,
            'fecha_solicitud' => sprintf('%d-03-01', $currentYear - 1),
            'estado_id' => $pendiente->id,
            'dias_respuesta' => 2,
        ]);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cliente-interno.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame($currentYear, $metrics['anio']);
        $this->assertSame(4, $metrics['total']);
        $this->assertSame(2, $metrics['tendencia_mensual'][1]);
        $this->assertSame(2, $metrics['tendencia_mensual'][6]);
        $this->assertSame(0, $metrics['tendencia_mensual'][2]);
        $this->assertSame(
            ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            $metrics['charts']['tendencia_mensual']['labels']
        );
        $this->assertSame([2, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0], $metrics['charts']['tendencia_mensual']['data']);

        $porEstado = collect($metrics['por_estado'])->keyBy('estado');
        $this->assertSame(2, $porEstado->get('Pendiente')['total']);
        $this->assertSame(1, $porEstado->get('Respondida')['total']);
        $this->assertSame(1, $porEstado->get('Sin estado')['total']);
        $this->assertContains('Sin estado', $metrics['charts']['por_estado']['labels']);

        $this->assertSame(3, $metrics['dias_respuesta']['con_dato']);
        $this->assertEqualsWithDelta(5.7, (float) $metrics['dias_respuesta']['promedio'], 0.01);
        $distribucion = collect($metrics['dias_respuesta']['distribucion'])->keyBy('label');
        $this->assertSame(1, $distribucion->get('0–2')['total']);
        $this->assertSame(1, $distribucion->get('3–5')['total']);
        $this->assertSame(0, $distribucion->get('6–10')['total']);
        $this->assertSame(1, $distribucion->get('11+')['total']);
        $this->assertSame(['0–2', '3–5', '6–10', '11+'], $metrics['charts']['dias_respuesta']['labels']);
        $this->assertSame([1, 1, 0, 1], $metrics['charts']['dias_respuesta']['data']);
    }

    public function test_metrics_filter_by_anio_and_mes(): void
    {
        $viewer = $this->viewerUser();
        $currentYear = (int) now()->year;
        $previousYear = $currentYear - 1;
        $pendiente = ClienteInternoEstado::query()->where('code', 'PENDIENTE')->firstOrFail();

        ClienteInternoSolicitud::factory()->count(2)->create([
            'anio' => $currentYear,
            'mes' => 2,
            'fecha_solicitud' => sprintf('%d-02-01', $currentYear),
            'estado_id' => $pendiente->id,
            'dias_respuesta' => 3,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'anio' => $currentYear,
            'mes' => 3,
            'fecha_solicitud' => sprintf('%d-03-01', $currentYear),
            'estado_id' => $pendiente->id,
            'dias_respuesta' => 8,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'anio' => $previousYear,
            'mes' => 4,
            'fecha_solicitud' => sprintf('%d-04-01', $previousYear),
            'estado_id' => null,
            'dias_respuesta' => 1,
        ]);

        $byAnio = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cliente-interno.dashboard.metrics', [
                'anio' => $previousYear,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame($previousYear, $byAnio['anio']);
        $this->assertSame(1, $byAnio['total']);
        $this->assertSame(1, $byAnio['tendencia_mensual'][4]);
        $this->assertEqualsWithDelta(1.0, (float) $byAnio['dias_respuesta']['promedio'], 0.01);

        $byMes = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cliente-interno.dashboard.metrics', [
                'anio' => $currentYear,
                'mes' => 2,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(2, $byMes['total']);
        $this->assertEqualsWithDelta(3.0, (float) $byMes['dias_respuesta']['promedio'], 0.01);
        // Tendencia mensual sigue siendo del año completo (no se reduce por mes).
        $this->assertSame(2, $byMes['tendencia_mensual'][2]);
        $this->assertSame(1, $byMes['tendencia_mensual'][3]);
        $this->assertSame('2', $byMes['filters']['mes']);
    }

    public function test_default_anio_falls_back_to_latest_with_data(): void
    {
        $viewer = $this->viewerUser();
        $pastYear = (int) now()->year - 2;

        ClienteInternoSolicitud::factory()->create([
            'anio' => $pastYear,
            'mes' => 8,
            'fecha_solicitud' => sprintf('%d-08-01', $pastYear),
            'estado_id' => null,
            'dias_respuesta' => 2,
        ]);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cliente-interno.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame($pastYear, $metrics['anio']);
        $this->assertSame(1, $metrics['total']);
        $this->assertSame(1, $metrics['tendencia_mensual'][8]);
    }

    public function test_dias_respuesta_null_excluded_from_average_and_bins(): void
    {
        $viewer = $this->viewerUser();
        $year = (int) now()->year;

        ClienteInternoSolicitud::factory()->create([
            'anio' => $year,
            'mes' => 1,
            'fecha_solicitud' => sprintf('%d-01-01', $year),
            'dias_respuesta' => null,
        ]);
        ClienteInternoSolicitud::factory()->create([
            'anio' => $year,
            'mes' => 1,
            'fecha_solicitud' => sprintf('%d-01-02', $year),
            'dias_respuesta' => 10,
        ]);

        $metrics = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.cliente-interno.dashboard.metrics', ['anio' => $year]))
            ->assertOk()
            ->json();

        $this->assertSame(2, $metrics['total']);
        $this->assertSame(1, $metrics['dias_respuesta']['con_dato']);
        $this->assertEqualsWithDelta(10.0, (float) $metrics['dias_respuesta']['promedio'], 0.01);
        $this->assertSame(1, collect($metrics['dias_respuesta']['distribucion'])->keyBy('label')->get('6–10')['total']);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.solicitudes.view',
        ]);

        return $user;
    }

    private function catalogUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.cliente_interno',
            'cliente_interno.parameters.edit',
        ]);

        return $user;
    }
}
