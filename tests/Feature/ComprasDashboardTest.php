<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderEvaluation;
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
        $response->assertSee('Dashboard de Compras');
        $response->assertSee('Solicitudes');
        $response->assertSee('Órdenes');
        $response->assertSee('id="compras-chart-data"', false);
        $response->assertSee('id="trendChart"', false);
        $response->assertSee('id="bandejaStatusChart"', false);
        $response->assertSee('compras-dashboard-charts', false);
        $response->assertSee('Por autorizar');
        $response->assertSee('Por comprar');
        $response->assertSee('Con orden');
        $response->assertSee('#ea580c');
        $response->assertSee('#1984c7');
        $response->assertSee('#15803d');
    }

    public function test_ordenes_tab_renders_order_kpis_and_charts(): void
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

        $response = $this->actingAs($user)->get(route('compras.dashboard', ['tab' => 'ordenes']));

        $response->assertOk();
        $response->assertSee('Compras aprobadas');
        $response->assertSee('Compras realizadas');
        $response->assertSee('id="ordersTrendChart"', false);
        $response->assertSee('id="ordersCoverageChart"', false);
        $response->assertDontSee('id="trendChart"', false);
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
            'created_at' => Carbon::parse($august),
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
        $this->assertSame(2, $dashboardStats['bandeja_total']);
        $this->assertSame(1, $dashboardStats['bandeja_en_curso']);

        $bandejaLinks = ComprasQueueFilterBag::bandejaLinkQuery($filters);
        $this->assertSame('2026-08-01', $bandejaLinks['date_from']);
        $this->assertSame('2026-08-31', $bandejaLinks['date_to']);

        Carbon::setTestNow();
    }

    public function test_ordenes_kpis_count_approved_requests_and_emitted_orders(): void
    {
        Carbon::setTestNow('2026-08-20 10:00:00');

        $director = User::factory()->create(['must_change_password' => false]);
        $director->assignRole('director');
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);

        $approved = $this->createApprovedPurchase($requester, $director, 9101, '2026-08-01', PurchaseRequest::COMPRAS_EN_CURSO);
        $this->createApprovedPurchase($requester, $director, 9102, '2026-08-02', PurchaseRequest::COMPRAS_PENDIENTE);
        $this->createApprovedPurchase($requester, $director, 9103, '2026-07-10', PurchaseRequest::COMPRAS_PENDIENTE);

        $this->emitOrder($approved, $requester, '2026-08-10');
        $this->emitOrder($approved, $requester, '2026-08-15');
        $this->emitOrder($approved, $requester, '2026-08-18', PurchaseOrder::ESTADO_ANULADA);

        $orderStats = app(ComprasDashboardService::class)->build([
            'year' => 2026,
            'month' => 8,
            'area_key' => '',
            'tipo' => '',
            'tab' => 'ordenes',
        ])['orderStats'];

        $this->assertSame(2, $orderStats['aprobadas']);
        $this->assertSame(2, $orderStats['realizadas']);
        $this->assertSame('2 / 2', $orderStats['relacion_display']);
        $this->assertSame(100.0, $orderStats['relacion_pct']);

        Carbon::setTestNow();
    }

    public function test_ordenes_cycle_days_average_counts_each_order(): void
    {
        Carbon::setTestNow('2026-08-20 10:00:00');

        $director = User::factory()->create(['must_change_password' => false]);
        $director->assignRole('director');
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);

        $approved = $this->createApprovedPurchase(
            $requester,
            $director,
            9201,
            '2026-08-01',
            PurchaseRequest::COMPRAS_EN_CURSO,
            '2026-08-05',
        );
        $this->emitOrder($approved, $requester, '2026-08-10');
        $this->emitOrder($approved, $requester, '2026-08-15');

        $orderStats = app(ComprasDashboardService::class)->build([
            'year' => 2026,
            'month' => 8,
            'area_key' => '',
            'tipo' => 'purchase',
            'tab' => 'ordenes',
        ])['orderStats'];

        $this->assertSame(7.5, $orderStats['ciclo_aprobacion']);
        $this->assertSame(11.5, $orderStats['ciclo_solicitud']);

        Carbon::setTestNow();
    }

    public function test_ordenes_evaluation_average_and_coverage(): void
    {
        Carbon::setTestNow('2026-08-20 10:00:00');

        $director = User::factory()->create(['must_change_password' => false]);
        $director->assignRole('director');
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);

        $approved = $this->createApprovedPurchase($requester, $director, 9301, '2026-08-01', PurchaseRequest::COMPRAS_EN_CURSO);
        $first = $this->emitOrder($approved, $requester, '2026-08-10');
        $second = $this->emitOrder($approved, $requester, '2026-08-12');
        $this->emitOrder($approved, $requester, '2026-08-14');

        PurchaseOrderEvaluation::factory()->create([
            'purchase_order_id' => $first->id,
            'puntaje_porcentaje' => 100,
            'evaluated_by' => $requester->id,
        ]);
        PurchaseOrderEvaluation::factory()->create([
            'purchase_order_id' => $second->id,
            'puntaje_porcentaje' => 80,
            'evaluated_by' => $requester->id,
        ]);

        $orderStats = app(ComprasDashboardService::class)->build([
            'year' => 2026,
            'month' => 8,
            'area_key' => '',
            'tipo' => 'purchase',
            'tab' => 'ordenes',
        ])['orderStats'];

        $this->assertSame(90.0, $orderStats['evaluacion_promedio']);
        $this->assertSame(2, $orderStats['evaluadas']);
        $this->assertSame(3, $orderStats['realizadas']);
        $this->assertSame('2 / 3', $orderStats['cobertura_display']);
        $this->assertSame(66.7, $orderStats['cobertura_pct']);

        Carbon::setTestNow();
    }

    private function createApprovedPurchase(
        User $requester,
        User $director,
        int $numero,
        string $fechaSolicitud,
        string $estadoCompras,
        ?string $fechaAprobacion = null,
    ): PurchaseRequest {
        $submittedAt = Carbon::parse($fechaSolicitud)->setTime(10, 30, 0);
        $fechaAprobacion ??= $fechaSolicitud;

        $purchaseRequest = PurchaseRequest::query()->create([
            'numero_solicitud' => $numero,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => $fechaSolicitud,
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => $estadoCompras,
            'fecha_aprobacion' => $fechaAprobacion,
        ]);

        $purchaseRequest->forceFill([
            'created_at' => $submittedAt,
            'updated_at' => $submittedAt,
        ])->saveQuietly();

        return $purchaseRequest;
    }

    private function emitOrder(
        PurchaseRequest $purchaseRequest,
        User $createdBy,
        string $fecha,
        string $estado = PurchaseOrder::ESTADO_EMITIDA,
    ): PurchaseOrder {
        return PurchaseOrder::factory()->create([
            'source_type' => 'purchase_request',
            'source_id' => $purchaseRequest->id,
            'fecha' => $fecha,
            'estado' => $estado,
            'created_by' => $createdBy->id,
        ]);
    }
}
