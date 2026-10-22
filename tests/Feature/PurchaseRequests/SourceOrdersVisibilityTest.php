<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El solicitante y el director ven las OC de su pedido en el detalle y descargan su PDF sin permisos de Compras.
 */
class SourceOrdersVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $this->compras->givePermissionTo('purchase.tab.processing');
    }

    public function test_requester_and_director_see_orders_and_download_pdf(): void
    {
        [$requester, $director, $purchase] = $this->purchaseScenario();
        $order = $this->emitPurchaseOrder($purchase, 2, 150);
        $annulled = $this->emitPurchaseOrder($purchase, 1, 80);
        $annulled->update(['estado' => PurchaseOrder::ESTADO_ANULADA]);

        foreach ([$requester, $director] as $user) {
            $this->actingAs($user)
                ->get(route('purchase-requests.show', ['module' => 'compras', 'purchase_request' => $purchase->id, 'from' => 'mis_solicitudes']))
                ->assertOk()
                ->assertSee('Órdenes de compra')
                ->assertSee('Proveedor de prueba')
                ->assertSee('300.00')
                ->assertSee('Anulada')
                ->assertSee('Ver líneas y valores unitarios')
                ->assertSee('Pendiente');

            $this->actingAs($user)
                ->get(route('purchase-requests.source-orders.pdf', ['module' => 'compras', 'purchase_request' => $purchase->id, 'purchase_order' => $order->id]))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }

        // Sin permisos de Compras, las rutas de Órdenes siguen cerradas para el solicitante.
        $this->actingAs($requester)
            ->get(route('purchase-requests.orders.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertForbidden();
    }

    public function test_pdf_is_blocked_for_other_users_and_for_orders_of_another_request(): void
    {
        [, $director, $purchase] = $this->purchaseScenario();
        $order = $this->emitPurchaseOrder($purchase, 1, 100);

        $stranger = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $stranger->givePermissionTo(['purchase.tab.create', 'purchase.tab.my_requests']);

        $this->actingAs($stranger)
            ->get(route('purchase-requests.source-orders.pdf', ['module' => 'compras', 'purchase_request' => $purchase->id, 'purchase_order' => $order->id]))
            ->assertForbidden();

        [$otherRequester, , $otherPurchase] = $this->purchaseScenario(8802, 'otro.director@test.local');

        $this->actingAs($otherRequester)
            ->get(route('purchase-requests.source-orders.pdf', ['module' => 'compras', 'purchase_request' => $otherPurchase->id, 'purchase_order' => $order->id]))
            ->assertNotFound();
    }

    public function test_supply_requester_sees_orders_and_downloads_pdf(): void
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $owner = User::factory()->create(['area_key' => 'operaciones', 'sede_id' => $site->id, 'must_change_password' => false]);
        $owner->givePermissionTo('supply.tab.my_requests');

        $supply = SupplyRequest::query()->create([
            'user_id' => $owner->id,
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'status' => 'aprobada_calidad',
        ]);
        $supply->items()->create([
            'supply_product_id' => SupplyProduct::query()->firstOrFail()->id,
            'requested_quantity' => 5,
            'approved_quantity' => 5,
            'current_inventory' => 0,
            'is_not_in_catalog' => false,
        ]);

        $order = app(PurchaseOrderService::class)->createFromSupply($supply->fresh('items'), [
            'provider_id' => PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR INSUMOS'])->id,
            'fecha' => now()->toDateString(),
            'items' => [['cantidad' => 3, 'descripcion' => 'INSUMO', 'valor_unidad' => 10, 'iva' => 0]],
        ], $this->compras);

        $this->actingAs($owner)
            ->get(route('supplies.show', ['module' => 'operaciones', 'supply_request' => $supply->id]))
            ->assertOk()
            ->assertSee('Órdenes de compra')
            ->assertSee('PROVEEDOR INSUMOS')
            ->assertSee('Ordenado');

        $this->actingAs($owner)
            ->get(route('supplies.orders.pdf', ['module' => 'operaciones', 'supply_request' => $supply->id, 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        [, , $purchase] = $this->purchaseScenario(8803, 'tercer.director@test.local');
        $purchaseOrder = $this->emitPurchaseOrder($purchase, 1, 50);

        $this->actingAs($owner)
            ->get(route('supplies.orders.pdf', ['module' => 'operaciones', 'supply_request' => $supply->id, 'purchase_order' => $purchaseOrder->id]))
            ->assertNotFound();
    }

    /**
     * @return array{0: User, 1: User, 2: PurchaseRequest}
     */
    private function purchaseScenario(int $numero = 8801, string $directorEmail = 'director@test.local'): array
    {
        $requester = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $requester->givePermissionTo(['purchase.tab.create', 'purchase.tab.my_requests', 'view.board.compras.solicitudes_compra']);

        $director = User::factory()->create(['email' => $directorEmail, 'must_change_password' => false]);
        $director->assignRole('director');

        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => $numero,
            'user_id' => $requester->id,
            'area_key' => 'compras',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
        ]);
        $purchase->items()->create([
            'orden' => 1,
            'cantidad' => 5,
            'descripcion' => 'CAMARA',
            'referencia' => 'CAM-1',
            'utilizacion' => 'OFICINA',
            'ubicacion' => 'CALI',
        ]);

        return [$requester, $director, $purchase->fresh('items')];
    }

    private function emitPurchaseOrder(PurchaseRequest $purchase, int $cantidad, float $valorUnidad): PurchaseOrder
    {
        return app(PurchaseOrderService::class)->createFromPurchase($purchase->fresh('items'), [
            'provider_id' => PurchaseProvider::factory()->create(['nombre' => 'Proveedor de prueba'])->id,
            'fecha' => now()->toDateString(),
            'items' => [['cantidad' => $cantidad, 'descripcion' => 'CAMARA', 'valor_unidad' => $valorUnidad, 'iva' => 0]],
        ], $this->compras);
    }
}
