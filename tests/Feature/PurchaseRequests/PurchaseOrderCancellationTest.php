<?php

namespace Tests\Feature\PurchaseRequests;

use App\Mail\PurchaseOrderCancellationRequestedMail;
use App\Mail\PurchaseOrderCancellationResolvedMail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseFlowStateService;
use App\Services\PurchaseRequests\PurchaseOrderCancellationService;
use App\Services\PurchaseRequests\PurchaseOrderPdfExporter;
use App\Services\PurchaseRequests\PurchaseOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Anulación de OC: Compras la pide eligiendo director; el director aprueba o rechaza (plataforma o correo).
 */
class PurchaseOrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    private User $director;

    private User $otherDirector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $this->compras->givePermissionTo(['purchase.tab.processing', 'purchase.providers.edit']);

        $this->director = User::factory()->create(['email' => 'director.compras@test.local', 'must_change_password' => false]);
        $this->director->assignRole('director');

        $this->otherDirector = User::factory()->create(['email' => 'otro.director@test.local', 'must_change_password' => false]);
        $this->otherDirector->assignRole('director');
    }

    public function test_compras_requests_cancellation_choosing_the_director(): void
    {
        Mail::fake();
        [$purchase, $order] = $this->purchaseWithOrder();

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertSee('Solicitar anulación')
            ->assertSee($this->director->name);

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.cancellation.request', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'anulacion_aprobador_id' => $this->director->id,
                'motivo_anulacion' => 'proveedor equivocado',
            ])
            ->assertRedirect(route('purchase-requests.orders.show', ['module' => 'compras', 'purchase_order' => $order->id]));

        $order->refresh();
        $this->assertSame(PurchaseOrder::ESTADO_ANULACION_PENDIENTE, $order->estado);
        $this->assertSame($this->director->id, (int) $order->anulacion_aprobador_id);
        $this->assertSame('PROVEEDOR EQUIVOCADO', $order->anulacion_motivo);
        $this->assertStringContainsString('SOLICITUD DE ANULACIÓN OC N.º '.$order->numeroOc, (string) $purchase->comments()->reorder('id', 'desc')->value('body'));
        Mail::assertSent(PurchaseOrderCancellationRequestedMail::class, fn ($mail) => $mail->hasTo($this->director->email));

        // Mientras está pendiente, la cantidad sigue contando y no se puede evaluar.
        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $purchase->fresh()->estado_compras);
        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.evaluation.edit', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertNotFound();
    }

    public function test_request_requires_valid_director_and_order_without_receipt(): void
    {
        [, $order] = $this->purchaseWithOrder();
        $notDirector = User::factory()->create();

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.cancellation.request', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'anulacion_aprobador_id' => $notDirector->id,
                'motivo_anulacion' => 'error',
            ])
            ->assertSessionHasErrors('anulacion_aprobador_id');

        $order->evaluation()->create(['fecha_recibido' => now()->toDateString(), 'evaluated_by' => $this->compras->id]);

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.cancellation.request', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'anulacion_aprobador_id' => $this->director->id,
                'motivo_anulacion' => 'ya recibida',
            ])
            ->assertSessionHasErrors('motivo_anulacion');

        $this->assertSame(PurchaseOrder::ESTADO_EMITIDA, $order->fresh()->estado);
    }

    public function test_compras_can_withdraw_the_request(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();
        app(PurchaseOrderCancellationService::class)->request($order, $this->compras, $this->director->id, 'error');

        $this->actingAs($this->compras)
            ->delete(route('purchase-requests.orders.cancellation.withdraw', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(PurchaseOrder::ESTADO_EMITIDA, $order->estado);
        $this->assertNull($order->anulacion_aprobador_id);

        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras']))
            ->assertOk()
            ->assertDontSee('Anulaciones de órdenes por aprobar');
    }

    public function test_director_approves_in_app_and_the_balance_is_released(): void
    {
        Mail::fake();
        [$purchase, $order] = $this->purchaseWithOrder();
        app(PurchaseOrderCancellationService::class)->request($order, $this->compras, $this->director->id, 'proveedor equivocado');

        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras']))
            ->assertOk()
            ->assertSee('Anulaciones de órdenes por aprobar');

        $this->actingAs($this->otherDirector)
            ->get(route('purchase-requests.approval.order-cancellation.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertForbidden();

        $this->actingAs($this->otherDirector)
            ->patch(route('purchase-requests.approval.order-cancellation.update', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'decision' => 'aprobar',
            ])
            ->assertForbidden();

        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.order-cancellation.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertSee('Aprobar anulación');

        $this->actingAs($this->director)
            ->patch(route('purchase-requests.approval.order-cancellation.update', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'decision' => 'aprobar',
            ])
            ->assertRedirect(route('purchase-requests.approval.index', ['module' => 'compras']));

        $order->refresh();
        $this->assertSame(PurchaseOrder::ESTADO_ANULADA, $order->estado);
        $this->assertNotNull($order->anulacion_resuelta_at);
        Mail::assertSent(PurchaseOrderCancellationResolvedMail::class, fn ($mail) => $mail->hasTo($this->compras->email) && $mail->approved);

        // Sin otras órdenes, el pedido vuelve a Por comprar con todo el saldo disponible.
        $purchase->refresh();
        $this->assertSame(PurchaseRequest::COMPRAS_PENDIENTE, $purchase->estado_compras);
        $line = app(PurchaseOrderService::class)->sourceLines(PurchaseOrderService::SOURCE_PURCHASE, $purchase)[0];
        $this->assertSame(0.0, (float) $line['cantidad_ordenada']);
        $this->assertTrue($purchase->puedeDevolverAlSolicitante());

        $html = app(PurchaseOrderPdfExporter::class)->html($order->fresh());
        $this->assertStringContainsString('ANULADA', $html);
    }

    public function test_director_rejection_requires_comment_and_keeps_the_order(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();
        app(PurchaseOrderCancellationService::class)->request($order, $this->compras, $this->director->id, 'error');

        $this->actingAs($this->director)
            ->patch(route('purchase-requests.approval.order-cancellation.update', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'decision' => 'rechazar',
            ])
            ->assertSessionHasErrors('comentario_anulacion');

        $this->actingAs($this->director)
            ->patch(route('purchase-requests.approval.order-cancellation.update', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'decision' => 'rechazar',
                'comentario_anulacion' => 'la orden es correcta',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(PurchaseOrder::ESTADO_EMITIDA, $order->estado);
        $this->assertSame('LA ORDEN ES CORRECTA', $order->anulacion_respuesta);
        Mail::assertSent(PurchaseOrderCancellationResolvedMail::class, fn ($mail) => ! $mail->approved);
    }

    public function test_director_approves_from_the_signed_email_link(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();
        $service = app(PurchaseOrderCancellationService::class);
        $service->request($order, $this->compras, $this->director->id, 'duplicada');

        $this->get($service->emailShowUrl($order->fresh()))
            ->assertOk()
            ->assertSee('Aprobar anulación');

        $this->get(route('purchase-requests.order-cancellation-email.show', ['purchase_order' => $order->id, 'director' => $this->director->id]))
            ->assertForbidden();

        $this->post($service->emailUpdateUrl($order->fresh()), ['decision' => 'aprobar'])
            ->assertOk()
            ->assertSee('aprobada');

        $this->assertSame(PurchaseOrder::ESTADO_ANULADA, $order->fresh()->estado);
    }

    public function test_cancelling_a_supply_order_returns_the_supply_to_por_comprar(): void
    {
        Mail::fake();
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $owner = User::factory()->create(['area_key' => 'operaciones', 'sede_id' => $site->id]);

        $supply = SupplyRequest::query()->create([
            'user_id' => $owner->id,
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'status' => 'aprobada_calidad',
        ]);
        $supply->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => 4,
            'approved_quantity' => 4,
            'current_inventory' => 0,
            'is_not_in_catalog' => false,
        ]);

        $order = app(PurchaseOrderService::class)->createFromSupply($supply->fresh('items'), [
            'provider_id' => PurchaseProvider::factory()->create()->id,
            'fecha' => now()->toDateString(),
            'items' => [['cantidad' => 2, 'descripcion' => 'INSUMO', 'valor_unidad' => 10, 'iva' => 0]],
        ], $this->compras);
        app(PurchaseFlowStateService::class)->sync($supply->fresh(), $this->compras);
        $this->assertSame('en_compras', $supply->fresh()->status);

        $service = app(PurchaseOrderCancellationService::class);
        $service->request($order, $this->compras, $this->director->id, 'cantidad errada');
        $service->resolve($order->fresh(), $this->director->id, true, null);

        $this->assertSame(PurchaseOrder::ESTADO_ANULADA, $order->fresh()->estado);
        $this->assertSame('aprobada_calidad', $supply->fresh()->status);
        $this->assertStringContainsString('ANULACIÓN OC N.º '.$order->numeroOc.' APROBADA', (string) $supply->comments()->reorder('id', 'desc')->value('body'));
    }

    /**
     * @return array{0: PurchaseRequest, 1: PurchaseOrder}
     */
    private function purchaseWithOrder(): array
    {
        $requester = User::factory()->create(['area_key' => 'operaciones']);

        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => 7700,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $this->director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
        ]);
        $purchase->items()->create([
            'orden' => 1,
            'cantidad' => 3,
            'descripcion' => 'CAMARA',
            'referencia' => 'N/A',
            'utilizacion' => 'OFICINA',
            'ubicacion' => 'CALI',
        ]);

        $order = app(PurchaseOrderService::class)->createFromPurchase($purchase->fresh('items'), [
            'provider_id' => PurchaseProvider::factory()->create()->id,
            'fecha' => now()->toDateString(),
            'items' => [['cantidad' => 2, 'descripcion' => 'CAMARA', 'valor_unidad' => 100, 'iva' => 0]],
        ], $this->compras);

        app(PurchaseFlowStateService::class)->sync($purchase->fresh(), $this->compras);

        return [$purchase->fresh(), $order->fresh()];
    }
}
