<?php

namespace Tests\Feature;

use App\Models\PersonalRequisition;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionsDashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_dashboard_renders_apexcharts_containers_without_chartjs_cdn(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'gestion_humana',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.dashboard',
        ]);

        $response = $this->actingAs($user)->get(route('requisitions.dashboard', [
            'module' => 'gestion_humana',
            'year' => now()->year,
        ]));

        $response->assertOk();
        $response->assertSee('id="requisitions-chart-data"', false);
        $response->assertSee('id="trendChart"', false);
        $response->assertSee('id="statusChart"', false);
        $response->assertSee('id="cityChart"', false);
        $response->assertSee('id="clientChart"', false);
        $response->assertSee('requisitions-dashboard-charts', false);
        $response->assertSee('name="recruiter_id"', false);
        $response->assertSee('Sin reclutador', false);
        $response->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false);
        $response->assertDontSee('new Chart(', false);
        $response->assertDontSee('<canvas', false);
    }

    public function test_dashboard_defaults_month_to_current_and_kpis_respect_all_filters(): void
    {
        $viewer = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'gestion_humana',
        ]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo([
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.dashboard',
        ]);

        $requester = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $recruiter = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
            'name' => 'Reclutador Dashboard KPI',
        ]);
        $recruiter->assignRole('usuario');
        $recruiter->givePermissionTo('requisitions.selection_officer');

        $base = $this->dashboardRequisitionAttributes($requester);

        PersonalRequisition::create(array_merge($base, [
            'code' => 'REQ-2026-DASH-001',
            'recruiter_id' => $recruiter->id,
            'status' => PersonalRequisition::STATUS_EN_GESTION,
        ]));
        PersonalRequisition::create(array_merge($base, [
            'code' => 'REQ-2026-DASH-002',
            'recruiter_id' => null,
            'status' => PersonalRequisition::STATUS_SOLICITADA,
        ]));
        PersonalRequisition::create(array_merge($base, [
            'code' => 'REQ-2026-DASH-003',
            'recruiter_id' => $recruiter->id,
            'status' => PersonalRequisition::STATUS_SOLICITADA,
            'request_date' => now()->subYear()->toDateString(),
        ]));

        $defaultResponse = $this->actingAs($viewer)->get(route('requisitions.dashboard', [
            'module' => 'gestion_humana',
        ]));

        $defaultResponse->assertOk();
        $defaultResponse->assertViewHas('filters', function (array $filters): bool {
            return (int) $filters['year'] === (int) now()->year
                && (int) $filters['month'] === (int) now()->month;
        });

        $response = $this->actingAs($viewer)->get(route('requisitions.dashboard', [
            'module' => 'gestion_humana',
            'year' => now()->year,
            'month' => now()->month,
            'recruiter_id' => $recruiter->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['total'] === 1
                && $stats['en_gestion'] === 1
                && $stats['solicitada'] === 0;
        });
        $response->assertSee('Reclutador Dashboard KPI', false);
    }

    public function test_dashboard_kpi_links_carry_filters_to_manage(): void
    {
        $viewer = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'gestion_humana',
        ]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo([
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.dashboard',
            'requisitions.tab.gestion',
        ]);

        $client = RequisitionClient::query()->firstOrFail();
        $position = RequisitionPosition::query()->firstOrFail();
        $city = RequisitionCity::query()->firstOrFail();
        $recruiter = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
            'name' => 'Reclutador KPI Link',
        ]);
        $recruiter->assignRole('usuario');
        $recruiter->givePermissionTo('requisitions.selection_officer');

        $year = (int) now()->year;
        $month = (int) now()->month;
        $dateFrom = now()->startOfMonth()->toDateString();
        $dateTo = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($viewer)->get(route('requisitions.dashboard', [
            'module' => 'gestion_humana',
            'year' => $year,
            'month' => $month,
            'client_id' => $client->id,
            'position_id' => $position->id,
            'city_id' => $city->id,
            'recruiter_id' => $recruiter->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('kpiUrls', function (array $kpiUrls) use ($client, $position, $city, $recruiter, $dateFrom, $dateTo): bool {
            foreach (['total', 'solicitada', 'en_gestion', 'contratado', 'cancelada'] as $key) {
                if (! isset($kpiUrls[$key]) || ! is_string($kpiUrls[$key])) {
                    return false;
                }
            }

            parse_str(parse_url($kpiUrls['total'], PHP_URL_QUERY) ?: '', $totalQuery);
            parse_str(parse_url($kpiUrls['en_gestion'], PHP_URL_QUERY) ?: '', $enGestionQuery);

            return ($totalQuery['include_closed'] ?? null) === '1'
                && ($totalQuery['client_id'] ?? null) === (string) $client->id
                && ($totalQuery['position_id'] ?? null) === (string) $position->id
                && ($totalQuery['city_id'] ?? null) === (string) $city->id
                && ($totalQuery['recruiter_id'] ?? null) === (string) $recruiter->id
                && ($totalQuery['date_from'] ?? null) === $dateFrom
                && ($totalQuery['date_to'] ?? null) === $dateTo
                && ! isset($totalQuery['status'])
                && ($enGestionQuery['status'] ?? null) === PersonalRequisition::STATUS_EN_GESTION
                && ($enGestionQuery['client_id'] ?? null) === (string) $client->id
                && ! isset($enGestionQuery['include_closed']);
        });

        $manage = $this->actingAs($viewer)->get(route('requisitions.manage', [
            'module' => 'gestion_humana',
            'status' => PersonalRequisition::STATUS_EN_GESTION,
            'client_id' => $client->id,
            'position_id' => $position->id,
            'city_id' => $city->id,
            'recruiter_id' => $recruiter->id,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]));

        $manage->assertOk();
        $manage->assertSee('id="manage-client-select"', false);
        $manage->assertSee('id="manage-position-select"', false);
        $manage->assertSee('id="manage-city-select"', false);
        $manage->assertViewHas('filters', function (array $filters) use ($client, $position, $city, $recruiter, $dateFrom, $dateTo): bool {
            return ($filters['status'] ?? null) === PersonalRequisition::STATUS_EN_GESTION
                && (int) ($filters['client_id'] ?? 0) === $client->id
                && (int) ($filters['position_id'] ?? 0) === $position->id
                && (int) ($filters['city_id'] ?? 0) === $city->id
                && ($filters['recruiter_id'] ?? null) === (string) $recruiter->id
                && ($filters['date_from'] ?? null) === $dateFrom
                && ($filters['date_to'] ?? null) === $dateTo;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardRequisitionAttributes(User $requester): array
    {
        return [
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => 'gestion_humana',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => 'gestion_humana',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => 'Perfil dashboard',
            'service_structure' => 'Estructura de prueba',
            'cost_center' => 'CC-DASH',
            'status' => PersonalRequisition::STATUS_SOLICITADA,
            'status_changed_at' => now(),
        ];
    }
}
