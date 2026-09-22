<?php

namespace Tests\Feature\GestionHumana;

use App\Models\CommercialClient;
use App\Models\PayrollCatalogItem;
use App\Models\SeleccionExamenOcupacional;
use App\Models\SeleccionIngreso;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeleccionDashboardTest extends TestCase
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
            ->get(route('gestion-humana.seleccion.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.seleccion.dashboard.metrics'))
            ->assertForbidden();
    }

    public function test_dashboard_renders_and_metrics_respect_filters(): void
    {
        $viewer = $this->viewerUser();
        $responsableA = User::factory()->create([
            'name' => 'Responsable A',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $responsableB = User::factory()->create([
            'name' => 'Responsable B',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $clientA = CommercialClient::query()->firstOrCreate(
            ['nit' => '900111001-1'],
            ['name' => 'Cliente Dashboard A', 'city' => 'Cali'],
        );
        $clientB = CommercialClient::query()->firstOrCreate(
            ['nit' => '900111002-2'],
            ['name' => 'Cliente Dashboard B', 'city' => 'Cali'],
        );

        $this->ensureSolicitudStatus('EN_PROCESO', 'EN PROCESO');
        $this->ensureSolicitudStatus('CONTRATADO', 'CONTRATADO');

        SeleccionIngreso::factory()->create([
            'commercial_client_id' => $clientA->id,
            'responsable_user_id' => $responsableA->id,
            'fecha_ingreso' => now()->toDateString(),
        ]);
        SeleccionIngreso::factory()->create([
            'commercial_client_id' => $clientB->id,
            'responsable_user_id' => $responsableB->id,
            'fecha_ingreso' => now()->subMonths(2)->toDateString(),
        ]);

        SeleccionExamenOcupacional::factory()->create([
            'commercial_client_id' => $clientA->id,
            'responsable_user_id' => $responsableA->id,
            'fecha_arl' => now()->toDateString(),
            'solicitud_status_code' => 'EN_PROCESO',
            'solicitud_status_name' => 'EN PROCESO',
        ]);
        SeleccionExamenOcupacional::factory()->create([
            'commercial_client_id' => $clientB->id,
            'responsable_user_id' => $responsableB->id,
            'fecha_arl' => now()->subMonths(1)->toDateString(),
            'solicitud_status_code' => 'CONTRATADO',
            'solicitud_status_name' => 'CONTRATADO',
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.seleccion.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('seleccion-dashboard-page', false)
            ->assertSee('Total ingresos', false)
            ->assertSee('Exámenes en proceso', false)
            ->assertSee('Tendencia mensual de ingresos', false);

        $all = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.seleccion.dashboard.metrics'))
            ->assertOk()
            ->json();

        $this->assertSame(2, $all['kpis']['total_ingresos']);
        $this->assertSame(2, $all['kpis']['total_examenes']);
        $this->assertSame(1, $all['kpis']['ingresos_del_mes']);
        $this->assertSame(1, $all['kpis']['examenes_en_proceso']);

        $this->assertArrayHasKey('by_solicitud_status', $all['charts']);
        $this->assertArrayHasKey('ingresos_trend', $all['charts']);
        $this->assertArrayHasKey('by_client', $all['charts']);
        $this->assertArrayHasKey('by_responsable', $all['charts']);
        $this->assertNotEmpty($all['charts']['ingresos_trend']['labels']);
        $this->assertSame(
            count($all['charts']['ingresos_trend']['labels']),
            count($all['charts']['ingresos_trend']['data']),
        );

        $solicitudLabels = $all['charts']['by_solicitud_status']['labels'];
        $this->assertContains('EN PROCESO', $solicitudLabels);
        $this->assertContains('CONTRATADO', $solicitudLabels);

        $filtered = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.seleccion.dashboard.metrics', [
                'commercial_client_id' => $clientA->id,
                'responsable_user_id' => $responsableA->id,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $filtered['kpis']['total_ingresos']);
        $this->assertSame(1, $filtered['kpis']['total_examenes']);
        $this->assertSame(1, $filtered['kpis']['ingresos_del_mes']);
        $this->assertSame(1, $filtered['kpis']['examenes_en_proceso']);
        $this->assertSame(['EN PROCESO'], $filtered['charts']['by_solicitud_status']['labels']);
        $this->assertSame([1], $filtered['charts']['by_solicitud_status']['data']);

        $dateFiltered = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.seleccion.dashboard.metrics', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $dateFiltered['kpis']['total_ingresos']);
        $this->assertSame(1, $dateFiltered['kpis']['total_examenes']);
    }

    private function ensureSolicitudStatus(string $code, string $name): void
    {
        PayrollCatalogItem::query()->firstOrCreate(
            ['catalog_type' => 'seleccion_solicitud_status', 'code' => $code],
            ['name' => $name, 'is_active' => true, 'sort_order' => 1],
        );
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'view.board.gestion_humana.seleccion',
            'seleccion.view',
        ]);

        return $user;
    }
}
