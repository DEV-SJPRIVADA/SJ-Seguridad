<?php

namespace Tests\Feature\PurchaseRequests;

use App\Mail\PurchaseOrderApprovalRequestedMail;
use App\Mail\PurchaseOrderApprovalResolvedMail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseFlowStateService;
use App\Services\PurchaseRequests\PurchaseOrderApprovalService;
use App\Services\PurchaseRequests\PurchaseOrderPdfExporter;
use App\Services\PurchaseRequests\PurchaseOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Aprobación opcional de OC por director o administrador (plataforma o correo); rechazar anula la orden.
 */
class PurchaseOrderApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    private User $director;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create(['name' => 'Analista Compras', 'area_key' => 'compras', 'must_change_password' => false]);
        $this->compras->givePermissionTo('purchase.tab.processing');

        $this->director = User::factory()->create(['name' => 'Directora Prueba', 'email' => 'directora@test.local', 'must_change_password' => false]);
        $this->director->assignRole('director');

        $this->admin = User::factory()->create(['name' => 'Administrador Prueba', 'email' => 'admin.oc@test.local', 'must_change_password' => false]);
        $this->admin->assignRole('administrador');
    }

    public function test_selector_lists_directors_and_administrators_and_request_notifies_approver(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertSee('Solicitar aprobación')
            ->assertSee('Directora Prueba')
            ->assertSee('Administrador Prueba');

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.approval.request', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'aprobacion_aprobador_id' => $this->admin->id,
                'aprobacion_nota' => 'urgente para el lunes',
            ])
            ->assertRedirect(route('purchase-requests.orders.show', ['module' => 'compras', 'purchase_order' => $order->id]));

        $order->refresh();
        $this->assertSame(PurchaseOrder::APROBACION_PENDIENTE, $order->aprobacion_estado);
        $this->assertSame(PurchaseOrder::ESTADO_EMITIDA, $order->estado);
        $this->assertSame('URGENTE PARA EL LUNES', $order->aprobacion_nota);
        $this->assertFalse($order->puedeSolicitarAnulacion());
        Mail::assertSent(PurchaseOrderApprovalRequestedMail::class, fn ($mail) => $mail->hasTo($this->admin->email));
    }

    public function test_administrator_approves_in_app_and_pdf_shows_the_approver(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();
        app(PurchaseOrderApprovalService::class)->request($order, $this->compras, $this->admin->id, null);

        $this->actingAs($this->admin)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes']))
            ->assertOk()
            ->assertSee('Solicitudes de Compra')
            ->assertSee('Órdenes de Compra')
            ->assertSee('Anulaciones de órdenes')
            ->assertSee('Revisar orden');

        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.order-approval.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch(route('purchase-requests.approval.order-approval.update', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'decision' => 'aprobar',
            ])
            ->assertRedirect(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes']));

        $order->refresh();
        $this->assertSame(PurchaseOrder::APROBACION_APROBADA, $order->aprobacion_estado);
        $this->assertSame(PurchaseOrder::ESTADO_EMITIDA, $order->estado);
        Mail::assertSent(PurchaseOrderApprovalResolvedMail::class, fn ($mail) => $mail->hasTo($this->compras->email) && $mail->approved);

        $html = app(PurchaseOrderPdfExporter::class)->html($order->fresh());
        $this->assertStringContainsString('Administrador Prueba', $html);

        // Ya resuelta: sale de Pendientes pero queda en el historial con su decisión.
        $this->actingAs($this->admin)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes']))
            ->assertOk()
            ->assertSee('No hay órdenes de compra pendientes de aprobación.')
            ->assertDontSee('Revisar orden');

        foreach (['todos', PurchaseOrder::APROBACION_APROBADA] as $estado) {
            $this->actingAs($this->admin)
                ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes', 'estado' => $estado]))
                ->assertOk()
                ->assertSee('Ver detalle')
                ->assertSee('status-pill--success', false);
        }

        $this->actingAs($this->admin)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes', 'estado' => PurchaseOrder::APROBACION_RECHAZADA]))
            ->assertOk()
            ->assertSee('No hay órdenes de compra con la vista seleccionada.');

        // Otro aprobador no ve el historial ajeno.
        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes', 'estado' => 'todos']))
            ->assertOk()
            ->assertSee('No hay órdenes de compra con la vista seleccionada.');
    }

    public function test_rejection_requires_comment_and_cancels_the_order(): void
    {
        Mail::fake();
        [$purchase, $order] = $this->purchaseWithOrder();
        app(PurchaseOrderApprovalService::class)->request($order, $this->compras, $this->director->id, null);

        $this->actingAs($this->director)
            ->patch(route('purchase-requests.approval.order-approval.update', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'decision' => 'rechazar',
            ])
            ->assertSessionHasErrors('comentario_aprobacion');

        $this->actingAs($this->director)
            ->patch(route('purchase-requests.approval.order-approval.update', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'decision' => 'rechazar',
                'comentario_aprobacion' => 'precio fuera de mercado',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(PurchaseOrder::APROBACION_RECHAZADA, $order->aprobacion_estado);
        $this->assertSame(PurchaseOrder::ESTADO_ANULADA, $order->estado);
        $this->assertSame('PRECIO FUERA DE MERCADO', $order->aprobacion_comentario);
        // La cantidad vuelve al saldo: el pedido regresa a Por comprar.
        $this->assertSame(PurchaseRequest::COMPRAS_PENDIENTE, $purchase->fresh()->estado_compras);
        Mail::assertSent(PurchaseOrderApprovalResolvedMail::class, fn ($mail) => ! $mail->approved);

        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes', 'estado' => PurchaseOrder::APROBACION_RECHAZADA]))
            ->assertOk()
            ->assertSee('Ver detalle')
            ->assertSee('status-pill--danger', false);
    }

    public function test_approver_decides_from_signed_email_link_and_compras_can_withdraw(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();
        $service = app(PurchaseOrderApprovalService::class);
        $service->request($order, $this->compras, $this->director->id, null);

        $this->get($service->emailShowUrl($order->fresh()))
            ->assertOk()
            ->assertSee('Aprobar orden');

        $this->post($service->emailUpdateUrl($order->fresh()), ['decision' => 'aprobar'])
            ->assertOk()
            ->assertSee('aprobada');

        $this->assertTrue($order->fresh()->isAprobada());

        // Retirar una solicitud pendiente.
        [, $other] = $this->purchaseWithOrder(7702);
        $service->request($other, $this->compras, $this->director->id, null);

        $this->actingAs($this->compras)
            ->delete(route('purchase-requests.orders.approval.withdraw', ['module' => 'compras', 'purchase_order' => $other->id]))
            ->assertRedirect();

        $this->assertNull($other->fresh()->aprobacion_estado);
        $this->assertTrue($other->fresh()->puedeSolicitarAprobacion());
    }

    public function test_request_rejects_users_who_are_not_director_or_administrator(): void
    {
        [, $order] = $this->purchaseWithOrder();
        $plain = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.approval.request', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'aprobacion_aprobador_id' => $plain->id,
            ])
            ->assertSessionHasErrors('aprobacion_aprobador_id');

        $this->assertNull($order->fresh()->aprobacion_estado);
    }

    /**
     * @return array{0: PurchaseRequest, 1: PurchaseOrder}
     */
    private function purchaseWithOrder(int $numero = 7701): array
    {
        $requester = User::factory()->create(['area_key' => 'operaciones']);

        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => $numero,
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
            'items' => [['cantidad' => 3, 'descripcion' => 'CAMARA', 'valor_unidad' => 100, 'iva_porcentaje' => 19]],
        ], $this->compras);

        app(PurchaseFlowStateService::class)->sync($purchase->fresh(), $this->compras);

        return [$purchase->fresh(), $order->fresh()];
    }
}
