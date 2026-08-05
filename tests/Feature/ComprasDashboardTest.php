<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\Compras\ComprasDashboardService;
use App\Services\Compras\ComprasQueueFilterBag;
use App\Services\Compras\ComprasQueueService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ComprasDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_compras_dashboard_renders_for_processing_user(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'view.dashboard',
            'purchase.tab.processing',
            'view.board.compras.dashboard',
            'view.board.compras.bandeja_compras',
        ]);

        $response = $this->actingAs($user)->get(route('compras.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Compras');
        $response->assertSee('id="compras-chart-data"', false);
        $response->assertSee('id="trendChart"', false);
        $response->assertSee('id="bandejaStatusChart"', false);
        $response->assertSee('compras-dashboard-charts', false);
    }

    public function test_guest_cannot_access_compras_dashboard(): void
    {
        $this->get(route('compras.dashboard'))->assertRedirect();
    }

    public function test_user_without_compras_scope_gets_forbidden(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo('purchase.tab.create');

        $this->actingAs($user)->get(route('compras.dashboard'))->assertForbidden();
    }

    public function test_director_with_approval_only_cannot_access_compras_dashboard(): void
    {
        $director = User::factory()->create(['must_change_password' => false]);
        $director->assignRole('director');

        $this->actingAs($director)->get(route('compras.dashboard'))->assertForbidden();
    }

    public function test_director_can_still_access_purchase_approval_queue(): void
    {
        $director = User::factory()->create(['must_change_password' => false]);
        $director->assignRole('director');

        $this->actingAs($director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras']))
            ->assertOk();
    }

    public function test_navigation_links_compras_dashboard_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'view.dashboard',
            'purchase.tab.processing',
            'view.board.compras.dashboard',
            'view.board.compras.bandeja_compras',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['module' => 'compras', 'board' => 'dashboard']))
            ->assertRedirect(route('compras.dashboard'));
    }

    public function test_bandeja_kpis_match_queue_when_month_filter_applied(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $director = User::factory()->create(['must_change_password' => false]);
        $director->assignRole('director');

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);

        $august = '2026-08-15';
        $july = '2026-07-10';

        $this->createApprovedPurchase($requester, $director, 9001, $august, PurchaseRequest::COMPRAS_PENDIENTE);
        $this->createApprovedPurchase($requester, $director, 9002, $august, PurchaseRequest::COMPRAS_EN_CURSO);
        $this->createApprovedPurchase($requester, $director, 9003, $august, PurchaseRequest::COMPRAS_COMPLETADO);
        $this->createApprovedPurchase($requester, $director, 9004, $august, PurchaseRequest::COMPRAS_COMPLETADO);
        $this->createApprovedPurchase($requester, $director, 9005, $july, PurchaseRequest::COMPRAS_PENDIENTE);
        $this->createApprovedPurchase($requester, $director, 9006, $july, PurchaseRequest::COMPRAS_EN_CURSO);

        SupplyRequest::query()->create([
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'sede_id' => SupplySite::query()->value('id'),
            'status' => 'completada',
            'updated_at' => Carbon::parse($august),
        ]);

        $filters = [
            'year' => 2026,
            'month' => 8,
            'area_key' => '',
            'tipo' => '',
        ];

        $queueStats = app(ComprasQueueService::class)->stats(
            ComprasQueueFilterBag::fromDashboardFilters($filters)
        );

        $dashboardStats = app(ComprasDashboardService::class)->build($filters)['stats'];

        $this->assertSame($queueStats['total'], $dashboardStats['bandeja_total']);
        $this->assertSame($queueStats['pendiente'], $dashboardStats['bandeja_pendiente']);
        $this->assertSame($queueStats['en_curso'], $dashboardStats['bandeja_en_curso']);
        $this->assertSame(5, $dashboardStats['bandeja_total']);
        $this->assertSame(1, $dashboardStats['bandeja_en_curso']);

        $bandejaLinks = ComprasQueueFilterBag::bandejaLinkQuery($filters);
        $this->assertSame('2026-08-01', $bandejaLinks['date_from']);
        $this->assertSame('2026-08-31', $bandejaLinks['date_to']);

        Carbon::setTestNow();
    }

    private function createApprovedPurchase(
        User $requester,
        User $director,
        int $numero,
        string $fechaAprobacion,
        string $estadoCompras,
    ): void {
        PurchaseRequest::query()->create([
            'numero_solicitud' => $numero,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => $fechaAprobacion,
            'descripcion' => "Solicitud {$numero}",
            'cantidad' => 1,
            'justificacion' => 'Prueba',
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => $estadoCompras,
            'fecha_aprobacion' => $fechaAprobacion,
        ]);
    }
}
