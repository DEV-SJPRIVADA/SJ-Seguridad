<?php

namespace Tests\Feature\GestionHumana;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DesvinculacionesDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        PermissionCatalog::sync();
        Carbon::setTestNow(Carbon::create(2026, 10, 15, 12, 0, 0, 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_requires_desvinculaciones_view(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('gestion-humana.desvinculaciones.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('gestion-humana.desvinculaciones.dashboard.metrics'))
            ->assertForbidden();
    }

    public function test_dashboard_renders_with_view_permission(): void
    {
        $viewer = $this->viewerUser();

        $this->actingAs($viewer)
            ->get(route('gestion-humana.desvinculaciones.dashboard'))
            ->assertOk()
            ->assertSee('Desvinculaciones del mes', false)
            ->assertSee('Desvinculaciones del año', false)
            ->assertDontSee('Recontratables', false)
            ->assertDontSee('Causales distintas', false)
            ->assertSee('Evolución mensual', false)
            ->assertSee('Por causal', false)
            ->assertSee('Por ciudad', false)
            ->assertSee('Por cliente', false)
            ->assertSee('Por cargo', false)
            ->assertSee('desv-chart-evolucion-mensual', false)
            ->assertSee('Dashboard', false);
    }

    public function test_metrics_count_bajas_by_month_and_causal(): void
    {
        $viewer = $this->viewerUser();

        $this->createClosedPeriod('1001', 'Ana Uno', [
            'termination_date' => '2026-10-05',
            'termination_cause_code' => 'RENUNCIA',
            'termination_cause_name' => 'Renuncia',
            'position_name' => 'GUARDA',
            'work_city_name' => 'Bogotá',
            'work_center_name' => 'Cliente A',
            'is_rehireable' => true,
        ]);
        $this->createClosedPeriod('1002', 'Luis Dos', [
            'termination_date' => '2026-10-12',
            'termination_cause_code' => 'RENUNCIA',
            'termination_cause_name' => 'Renuncia',
            'position_name' => 'GUARDA',
            'work_city_name' => 'Bogotá',
            'work_center_name' => 'Cliente A',
            'is_rehireable' => false,
        ]);
        $this->createClosedPeriod('1003', 'Maria Tres', [
            'termination_date' => '2026-09-20',
            'termination_cause_code' => 'JUSTA_CAUSA',
            'termination_cause_name' => 'Justa causa',
            'position_name' => 'ESCOLTA',
            'work_city_name' => 'Medellín',
            'work_center_name' => 'Cliente B',
            'is_rehireable' => false,
        ]);

        $payload = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.desvinculaciones.dashboard.metrics', [
                'anio' => 2026,
                'mes' => 10,
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(2, $payload['kpis']['bajas_mes']);
        $this->assertSame(3, $payload['kpis']['bajas_anio']);
        $this->assertArrayNotHasKey('recontratables_mes', $payload['kpis']);
        $this->assertArrayNotHasKey('causales_distintas_mes', $payload['kpis']);
        $this->assertSame(2, $payload['kpis']['top_causal_mes']);
        $this->assertSame('Renuncia', $payload['kpis']['top_causal_label']);

        $this->assertCount(10, $payload['charts']['evolucion_mensual']['labels']);
        $this->assertSame(2, $payload['charts']['evolucion_mensual']['data'][9]);
        $this->assertSame(1, $payload['charts']['evolucion_mensual']['data'][8]);

        $causal = array_combine(
            $payload['charts']['por_causal']['labels'],
            $payload['charts']['por_causal']['data']
        );
        $this->assertSame(2, $causal['Renuncia'] ?? 0);

        $ciudad = array_combine(
            $payload['charts']['por_ciudad']['labels'],
            $payload['charts']['por_ciudad']['data']
        );
        $this->assertSame(2, $ciudad['Bogotá'] ?? 0);
    }

    public function test_metrics_respect_city_filter(): void
    {
        $viewer = $this->viewerUser();

        $this->createClosedPeriod('2001', 'Bogota', [
            'termination_date' => '2026-10-01',
            'termination_cause_name' => 'Renuncia',
            'work_city_name' => 'Bogotá',
            'work_center_name' => 'Cliente A',
            'position_name' => 'GUARDA',
        ]);
        $this->createClosedPeriod('2002', 'Medellin', [
            'termination_date' => '2026-10-02',
            'termination_cause_name' => 'Renuncia',
            'work_city_name' => 'Medellín',
            'work_center_name' => 'Cliente B',
            'position_name' => 'GUARDA',
        ]);

        $payload = $this->actingAs($viewer)
            ->getJson(route('gestion-humana.desvinculaciones.dashboard.metrics', [
                'anio' => 2026,
                'mes' => 10,
                'ciudad' => 'Bogotá',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame(1, $payload['kpis']['bajas_mes']);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createClosedPeriod(string $document, string $fullName, array $extra = []): void
    {
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'hired_document' => $document,
            'hired_full_name' => $fullName,
            'moved_to_ficha_at' => now(),
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => $document,
            'full_name' => $fullName,
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'position_name' => $extra['position_name'] ?? 'GUARDA',
            'hire_date' => '2025-01-01',
            'termination_date' => $extra['termination_date'] ?? '2026-10-01',
            'work_city_name' => $extra['work_city_name'] ?? null,
            'work_center_name' => $extra['work_center_name'] ?? null,
            'cost_center_name' => $extra['cost_center_name'] ?? null,
        ]);

        EmployeeFichaEmploymentPeriod::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'sequence' => 1,
            'status' => EmployeeFichaEmploymentPeriod::STATUS_CERRADO,
            'hire_date' => '2025-01-01',
            'position_name' => $extra['position_name'] ?? 'GUARDA',
            'termination_date' => $extra['termination_date'] ?? '2026-10-01',
            'termination_cause_code' => $extra['termination_cause_code'] ?? 'RENUNCIA',
            'termination_cause_name' => $extra['termination_cause_name'] ?? 'Renuncia',
            'is_rehireable' => (bool) ($extra['is_rehireable'] ?? false),
            'work_center_name' => $extra['work_center_name'] ?? null,
        ]);
    }

    private function viewerUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo([
            'desvinculaciones.view',
            'view.board.gestion_humana.desvinculaciones',
        ]);

        return $user;
    }
}
