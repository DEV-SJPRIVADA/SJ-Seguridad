<?php

declare(strict_types=1);

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\Compras\ComprasDashboardService;
use App\Services\PurchaseRequests\PurchaseFlowStateService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-048 / T2 — la máquina escribe Por comprar, Con orden y Cerrado.
 */
class PurchaseFlowStateTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $this->compras->givePermissionTo('purchase.tab.processing');
    }

    public function test_approving_a_purchase_leaves_it_ready_to_buy(): void
    {
        $director = $this->director();
        $purchase = $this->pendingPurchase($director);

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'comentarios_director' => 'Puede comprarse',
        ])->assertRedirect();

        $purchase->refresh();

        $this->assertSame(PurchaseRequest::ESTADO_APROBADO, $purchase->estado);
        $this->assertSame(PurchaseRequest::COMPRAS_PENDIENTE, $purchase->estado_compras);
        $this->assertNull($purchase->comentarios_director);
        $this->assertSame('PUEDE COMPRARSE', $purchase->comments()->value('body'));
    }

    public function test_covering_the_balance_stays_in_progress_without_receipt_dates(): void
    {
        $purchase = $this->approvedPurchase(4);
        $supply = $this->approvedSupply(4);
        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), $this->orderPayload($provider->id, 2))->assertRedirect();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.supply.store', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]), $this->orderPayload($provider->id, 2))->assertRedirect();

        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $purchase->fresh()->estado_compras);
        $this->assertSame('en_compras', $supply->fresh()->status);

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), $this->orderPayload($provider->id, 2))->assertRedirect();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.supply.store', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]), $this->orderPayload($provider->id, 2))->assertRedirect();

        // T5: saldo en cero sin fecha de recibido sigue Con orden.
        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $purchase->fresh()->estado_compras);
        $this->assertSame('en_compras', $supply->fresh()->status);

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), $this->orderPayload($provider->id, 1))->assertSessionHasErrors();

        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $purchase->fresh()->estado_compras);
    }

    public function test_opening_process_does_not_change_the_supply_status(): void
    {
        $supply = $this->approvedSupply(4);

        $this->actingAs($this->compras)->get(route('purchase-requests.processing.supply', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]))
            ->assertOk()
            ->assertSee('Cerrar saldo')
            ->assertDontSee('name="estado_compras"', false);

        $this->assertSame('aprobada_calidad', $supply->fresh()->status);
        $this->assertNull($supply->fresh()->purchasing_manager_id);
    }

    public function test_requester_can_resubmit_a_supply_rejected_by_quality(): void
    {
        $owner = $this->supplyOwner();
        $product = SupplyProduct::query()->firstOrFail();
        $supply = $this->approvedSupply(2, 'rechazada_calidad', $owner);
        $supply->update(['quality_observations' => 'Falta justificar el pedido.']);

        $this->actingAs($owner)->patch(route('supplies.update', [
            'module' => 'operaciones',
            'supply_request' => $supply->id,
        ]), [
            'observations' => 'Pedido corregido',
            'items' => [[
                'type' => 'catalog',
                'product_id' => $product->id,
                'current_inventory' => 1,
                'quantity' => 3,
            ]],
        ])->assertRedirect();

        $supply->refresh();

        $this->assertSame('pendiente_calidad', $supply->status);
        $this->assertSame('Falta justificar el pedido.', $supply->quality_observations);
        $this->assertSame('PEDIDO CORREGIDO', $supply->observations);
        $this->assertSame(3, (int) $supply->items()->first()->requested_quantity);
    }

    public function test_close_balance_requires_a_reason_and_then_closes_even_with_remaining_quantity(): void
    {
        $purchase = $this->approvedPurchase(4);

        $this->actingAs($this->compras)->post(route('purchase-requests.processing.purchase.close', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), [])->assertSessionHasErrors('motivo');

        $this->assertSame(PurchaseRequest::COMPRAS_PENDIENTE, $purchase->fresh()->estado_compras);

        $this->actingAs($this->compras)->post(route('purchase-requests.processing.purchase.close', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), [
            'motivo' => 'El área ya no lo necesita',
        ])->assertRedirect();

        $purchase->refresh();

        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $purchase->estado_compras);
        $this->assertSame('EL ÁREA YA NO LO NECESITA', $purchase->comments()->value('body'));

        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), $this->orderPayload($provider->id, 1))->assertSessionHasErrors('items');

        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $purchase->fresh()->estado_compras);
        app(PurchaseFlowStateService::class)->sync($purchase->fresh());
        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $purchase->fresh()->estado_compras);
    }

    public function test_rejected_compras_status_is_not_moved_and_stays_out_of_the_queue(): void
    {
        $purchase = $this->approvedPurchase(2);
        $purchase->update(['estado_compras' => PurchaseRequest::COMPRAS_RECHAZADO]);

        app(PurchaseFlowStateService::class)->sync($purchase->fresh());

        $this->assertSame(PurchaseRequest::COMPRAS_RECHAZADO, $purchase->fresh()->estado_compras);

        $this->actingAs($this->compras)->get(route('purchase-requests.processing.index', [
            'module' => 'compras',
        ]))->assertOk()->assertDontSee($purchase->folio());

        $this->actingAs($this->compras)->get(route('purchase-requests.processing.index', [
            'module' => 'compras',
            'estado_compras' => PurchaseRequest::COMPRAS_COMPLETADO,
        ]))->assertOk()->assertDontSee($purchase->folio());

        $stats = app(ComprasDashboardService::class)->build([
            'year' => (int) now()->year,
            'month' => null,
            'area_key' => '',
            'tipo' => '',
        ])['stats'];

        $this->assertSame(0, $stats['bandeja_total']);
    }

    public function test_closed_supply_balance_does_not_inflate_the_dashboard_queue(): void
    {
        $open = $this->approvedSupply(2);
        $closed = $this->approvedSupply(2);
        $closed->update(['status' => 'completada']);

        $stats = app(ComprasDashboardService::class)->build([
            'year' => (int) now()->year,
            'month' => (int) now()->month,
            'area_key' => '',
            'tipo' => '',
        ])['stats'];

        $this->assertSame(1, $stats['bandeja_total']);
        $this->assertSame(1, $stats['bandeja_pendiente']);
        $this->assertSame('aprobada_calidad', $open->fresh()->status);
    }

    private function director(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('director');

        return $user;
    }

    private function supplyOwner(): User
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);
        $user->givePermissionTo('supply.tab.my_requests');

        return $user;
    }

    private function pendingPurchase(User $director): PurchaseRequest
    {
        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);

        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => 4801,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);

        $purchase->items()->create([
            'orden' => 1,
            'cantidad' => 1,
            'descripcion' => 'GUANTES',
            'referencia' => 'REF-1',
            'utilizacion' => 'VIGILANCIA',
            'ubicacion' => 'BOGOTA',
        ]);

        return $purchase;
    }

    private function approvedPurchase(int $cantidad): PurchaseRequest
    {
        $requester = User::factory()->create(['area_key' => 'operaciones', 'must_change_password' => false]);
        $director = User::factory()->create(['must_change_password' => false]);

        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => 4900 + $cantidad,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => $cantidad,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
            'fecha_aprobacion' => now()->toDateString(),
        ]);

        $purchase->items()->create([
            'orden' => 1,
            'cantidad' => $cantidad,
            'descripcion' => 'CAMARA',
            'referencia' => 'REF-OC',
            'utilizacion' => 'VIGILANCIA',
            'ubicacion' => 'BOGOTA',
        ]);

        return $purchase->load('items');
    }

    private function approvedSupply(int $cantidad, string $status = 'aprobada_calidad', ?User $owner = null): SupplyRequest
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $owner ??= User::factory()->create([
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);

        $supply = SupplyRequest::query()->create([
            'user_id' => $owner->id,
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => $status,
        ]);

        $supply->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => $cantidad,
            'approved_quantity' => $cantidad,
            'current_inventory' => 0,
            'is_not_in_catalog' => false,
        ]);

        return $supply->load('items');
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(int $providerId, float $cantidad): array
    {
        return [
            'provider_id' => $providerId,
            'fecha' => now()->toDateString(),
            'observaciones' => 'Entrega según lo acordado.',
            'items' => [[
                'cantidad' => $cantidad,
                'articulo' => 'REF-OC',
                'descripcion' => 'CAMARA',
                'valor_unidad' => 10,
                'iva' => 0,
            ]],
        ];
    }
}
