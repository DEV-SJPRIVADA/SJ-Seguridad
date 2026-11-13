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
use App\Services\Users\UserSignatureService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Autorización obligatoria de la OC por el director administrativo (plataforma o correo).
 * Sin firma no se imprime ni se evalúa; rechazar anula la orden y libera el saldo.
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

    public function test_selector_lists_directors_and_administrators_and_assigns_approver(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();

        // Toda OC nace pendiente de autorización, con el saldo reservado.
        $this->assertSame(PurchaseOrder::ESTADO_PENDIENTE_APROBACION, $order->estado);

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertSee('Asignar aprobador')
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
        $this->assertSame(PurchaseOrder::ESTADO_PENDIENTE_APROBACION, $order->estado);
        $this->assertSame('URGENTE PARA EL LUNES', $order->aprobacion_nota);
        $this->assertFalse($order->puedeSolicitarAnulacion());
        Mail::assertSent(PurchaseOrderApprovalRequestedMail::class, fn ($mail) => $mail->hasTo($this->admin->email));
    }

    public function test_pending_order_cannot_be_printed_nor_evaluated(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.pdf', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertForbidden();

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.excel', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertForbidden();

        // Quien evalúa tampoco entra: la OC aún no está autorizada (el 404 llega tras el permiso).
        $evaluator = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $evaluator->givePermissionTo(['purchase.tab.processing', 'purchase.providers.edit']);

        $this->actingAs($evaluator)
            ->get(route('purchase-requests.orders.evaluation.edit', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertNotFound();
    }

    public function test_pending_order_reserves_the_balance(): void
    {
        Mail::fake();
        [$purchase, $order] = $this->purchaseWithOrder();

        $this->assertSame(PurchaseOrder::ESTADO_PENDIENTE_APROBACION, $order->estado);

        $lines = app(PurchaseOrderService::class)->sourceLines(PurchaseOrderService::SOURCE_PURCHASE, $purchase->fresh('items'));

        // 3 solicitadas y 3 ordenadas en una OC pendiente: no queda saldo para emitir otra.
        $this->assertSame(0.0, (float) $lines[0]['cantidad_pendiente']);
    }

    public function test_compras_can_reassign_the_approver_while_pending(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();
        app(PurchaseOrderApprovalService::class)->request($order, $this->compras, $this->director->id, null);

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.approval.request', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'aprobacion_aprobador_id' => $this->admin->id,
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(PurchaseOrder::ESTADO_PENDIENTE_APROBACION, $order->estado);
        $this->assertSame($this->admin->id, (int) $order->aprobacion_aprobador_id);
        Mail::assertSent(PurchaseOrderApprovalRequestedMail::class, fn ($mail) => $mail->hasTo($this->admin->email));
    }

    public function test_administrator_approves_in_app_and_order_becomes_issued(): void
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

        // El aprobador elegido ve la orden con su resumen, líneas y el panel de decisión.
        $this->actingAs($this->admin)
            ->get(route('purchase-requests.approval.order-approval.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertSee('Aprobación de orden de compra')
            ->assertSee('Resumen de la orden')
            ->assertSee('Productos de la orden')
            ->assertSee('Aprobar orden');

        $this->actingAs($this->admin)
            ->patch(route('purchase-requests.approval.order-approval.update', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'decision' => 'aprobar',
            ])
            ->assertRedirect(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes']));

        $order->refresh();
        $this->assertSame(PurchaseOrder::APROBACION_APROBADA, $order->aprobacion_estado);
        // Aprobar habilita el documento: la OC pasa de pendiente a emitida.
        $this->assertSame(PurchaseOrder::ESTADO_EMITIDA, $order->estado);
        $this->assertTrue($order->estaAutorizada());
        Mail::assertSent(PurchaseOrderApprovalResolvedMail::class, fn ($mail) => $mail->hasTo($this->compras->email) && $mail->approved);

        $html = app(PurchaseOrderPdfExporter::class)->html($order->fresh());
        $this->assertStringContainsString('Administrador Prueba', $html);

        // Ya autorizada, el PDF y el Excel responden.
        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.pdf', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk();

        // Ya resuelta, el detalle muestra el resultado y oculta el formulario de decisión.
        $this->actingAs($this->admin)
            ->get(route('purchase-requests.approval.order-approval.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertSee('Resultado')
            ->assertDontSee('Aprobar orden');

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

    public function test_approver_decides_from_signed_email_link(): void
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

        $order->refresh();
        $this->assertTrue($order->isAprobada());
        $this->assertSame(PurchaseOrder::ESTADO_EMITIDA, $order->estado);
    }

    public function test_approver_cannot_be_changed_once_authorized(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();
        $service = app(PurchaseOrderApprovalService::class);
        $service->request($order, $this->compras, $this->director->id, null);
        $service->resolve($order->fresh(), $this->director->id, true, null);

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.approval.request', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'aprobacion_aprobador_id' => $this->admin->id,
            ])
            ->assertSessionHasErrors('aprobacion_aprobador_id');

        $this->assertSame($this->director->id, (int) $order->fresh()->aprobacion_aprobador_id);
    }

    public function test_approving_snapshots_signatures_into_the_pdf(): void
    {
        Mail::fake();
        Storage::fake('local');

        $gerente = User::factory()->create([
            'name' => 'Gerente Empresa',
            'email' => 'gerencia@sjsp.com.co',
            'must_change_password' => false,
        ]);

        $this->storeSignature($this->compras);
        $this->storeSignature($this->director);
        $this->storeSignature($this->admin);
        $this->storeSignature($gerente);

        [, $order] = $this->purchaseWithOrder();
        $service = app(PurchaseOrderApprovalService::class);
        $service->request($order, $this->compras, $this->admin->id, null);
        $service->resolve($order->fresh(), $this->admin->id, true, null);

        $order->refresh();
        $snapshots = $order->signature_snapshots;
        $this->assertIsArray($snapshots);
        $this->assertNotNull($snapshots['elaborado']['path']);
        $this->assertNotNull($snapshots['revisor']['path']);
        $this->assertNotNull($snapshots['aprobado']['path']);
        $this->assertNotNull($snapshots['gerente']['path']);
        $this->assertSame($gerente->id, $snapshots['gerente']['user_id']);

        $html = app(PurchaseOrderPdfExporter::class)->html($order);
        $this->assertSame(4, substr_count($html, 'class="user-signature"'));
        $this->assertStringContainsString('Gerente Empresa', $html);

        app(UserSignatureService::class)->delete($this->compras);
        app(UserSignatureService::class)->delete($gerente);

        $htmlAfter = app(PurchaseOrderPdfExporter::class)->html($order->fresh());
        $this->assertSame(4, substr_count($htmlAfter, 'class="user-signature"'));
        $this->assertStringContainsString('Gerente Empresa', $htmlAfter);
    }

    public function test_pdf_leaves_signature_boxes_blank_when_nobody_uploaded(): void
    {
        Mail::fake();
        Storage::fake('local');

        [, $order] = $this->purchaseWithOrder();
        $service = app(PurchaseOrderApprovalService::class);
        $service->request($order, $this->compras, $this->admin->id, null);
        $service->resolve($order->fresh(), $this->admin->id, true, null);

        $html = app(PurchaseOrderPdfExporter::class)->html($order->fresh());
        $this->assertStringNotContainsString('class="user-signature"', $html);
        $this->assertStringContainsString('Administrador Prueba', $html);
    }

    public function test_request_rejects_users_who_are_not_director_or_administrator(): void
    {
        Mail::fake();
        [, $order] = $this->purchaseWithOrder();
        $plain = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.approval.request', ['module' => 'compras', 'purchase_order' => $order->id]), [
                'aprobacion_aprobador_id' => $plain->id,
            ])
            ->assertSessionHasErrors('aprobacion_aprobador_id');

        // Sigue pendiente, sin aprobador asignado.
        $this->assertNull($order->fresh()->aprobacion_aprobador_id);
        $this->assertSame(PurchaseOrder::ESTADO_PENDIENTE_APROBACION, $order->fresh()->estado);
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

    private function storeSignature(User $user): void
    {
        app(UserSignatureService::class)->store(
            $user,
            UploadedFile::fake()->image('firma.png', 120, 40),
        );
    }
}
