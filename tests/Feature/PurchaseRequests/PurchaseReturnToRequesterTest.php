<?php

namespace Tests\Feature\PurchaseRequests;

use App\Enums\PedidoEtapa;
use App\Mail\PurchaseRequestCreatedMail;
use App\Mail\PurchaseRequestReturnedMail;
use App\Mail\SupplyRequestReturnedMail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestMailLog;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Compras devuelve un pedido mal diligenciado al solicitante; al reenviarlo se vuelve a autorizar.
 */
class PurchaseReturnToRequesterTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    private User $director;

    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create(['area_key' => 'compras', 'must_change_password' => false]);
        $this->compras->givePermissionTo(['purchase.tab.processing', 'view.board.compras.bandeja_compras']);

        $this->director = User::factory()->create(['email' => 'director@test.local', 'must_change_password' => false]);
        $this->director->assignRole('director');

        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $this->requester = User::factory()->create([
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);
        $this->requester->givePermissionTo([
            'purchase.tab.create',
            'purchase.tab.my_requests',
            'view.board.operaciones.solicitudes_compra',
            'supply.tab.my_requests',
        ]);
    }

    public function test_compras_returns_purchase_and_resubmit_goes_back_to_the_director(): void
    {
        Mail::fake();
        $purchase = $this->approvedPurchase();

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.processing.purchase', ['module' => 'compras', 'purchase_request' => $purchase->id]))
            ->assertOk()
            ->assertSee('Devolver al solicitante');

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.processing.purchase.return', ['module' => 'compras', 'purchase_request' => $purchase->id]), [
                'motivo_devolucion' => 'falta la referencia del producto',
            ])
            ->assertRedirect(route('purchase-requests.processing.index', ['module' => 'compras']));

        $purchase->refresh();
        $this->assertSame(PedidoEtapa::Devuelta, $purchase->etapa());
        $this->assertNull($purchase->estado_compras);
        $this->assertNull($purchase->fecha_aprobacion);
        $this->assertSame(
            'DEVUELTA POR COMPRAS: FALTA LA REFERENCIA DEL PRODUCTO',
            $purchase->comments()->latest('id')->value('body'),
        );
        Mail::assertSent(PurchaseRequestReturnedMail::class, fn ($mail) => $mail->hasTo($this->requester->email));
        $this->assertTrue($purchase->mailLogs()->where('mail_type', PurchaseRequestMailLog::TYPE_REQUESTER_RETURNED)->exists());

        // Ya no está en la bandeja de Pedidos.
        $this->actingAs($this->compras)
            ->get(route('purchase-requests.processing.index', ['module' => 'compras']))
            ->assertDontSee('pur-req-list__folio">'.$purchase->folioVisible(), false);

        // El solicitante corrige y reenvía: vuelve a Por autorizar con el director.
        $this->actingAs($this->requester)
            ->patch(route('purchase-requests.update', ['module' => 'operaciones', 'purchase_request' => $purchase->id]), [
                'area_key' => 'operaciones',
                'fecha_solicitud' => now()->toDateString(),
                'solicitud_para' => 'Interno',
                'urgente' => false,
                'aprobador_id' => $this->director->id,
                'items' => [[
                    'cantidad' => 2,
                    'descripcion' => 'Monitor',
                    'referencia' => 'DELL P2422H',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Cali',
                ]],
            ])
            ->assertRedirect(route('purchase-requests.index', ['module' => 'operaciones']));

        $purchase->refresh();
        $this->assertSame(PedidoEtapa::PorAutorizar, $purchase->etapa());
        $this->assertSame(PurchaseRequest::ESTADO_PENDIENTE, $purchase->estado);
        Mail::assertSent(PurchaseRequestCreatedMail::class, fn ($mail) => $mail->hasTo($this->director->email));
    }

    public function test_purchase_with_an_issued_order_cannot_be_returned(): void
    {
        Mail::fake();
        $purchase = $this->approvedPurchase();
        PurchaseOrder::factory()->create([
            'source_type' => 'purchase_request',
            'source_id' => $purchase->id,
        ]);

        $this->assertFalse($purchase->puedeDevolverAlSolicitante());

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.processing.purchase', ['module' => 'compras', 'purchase_request' => $purchase->id]))
            ->assertOk()
            ->assertDontSee('Devolver al solicitante');

        $this->actingAs($this->compras)
            ->from(route('purchase-requests.processing.purchase', ['module' => 'compras', 'purchase_request' => $purchase->id]))
            ->post(route('purchase-requests.processing.purchase.return', ['module' => 'compras', 'purchase_request' => $purchase->id]), [
                'motivo_devolucion' => 'mal diligenciada',
            ])
            ->assertSessionHasErrors('motivo_devolucion');

        $this->assertSame(PurchaseRequest::ESTADO_APROBADO, $purchase->fresh()->estado);
        Mail::assertNotSent(PurchaseRequestReturnedMail::class);
    }

    public function test_return_requires_motivo_and_processing_permission(): void
    {
        $purchase = $this->approvedPurchase();

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.processing.purchase.return', ['module' => 'compras', 'purchase_request' => $purchase->id]), [
                'motivo_devolucion' => '',
            ])
            ->assertSessionHasErrors('motivo_devolucion');

        $this->actingAs($this->requester)
            ->post(route('purchase-requests.processing.purchase.return', ['module' => 'compras', 'purchase_request' => $purchase->id]), [
                'motivo_devolucion' => 'intento sin permiso',
            ])
            ->assertForbidden();

        $this->assertSame(PurchaseRequest::ESTADO_APROBADO, $purchase->fresh()->estado);
    }

    public function test_compras_returns_supply_and_resubmit_goes_back_to_calidad(): void
    {
        Mail::fake();
        $supply = $this->approvedSupply();

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.processing.supply.return', ['module' => 'compras', 'supply_request' => $supply->id]), [
                'motivo_devolucion' => 'el producto no corresponde a la sede',
            ])
            ->assertRedirect(route('purchase-requests.processing.index', ['module' => 'compras']));

        $supply->refresh();
        $this->assertSame('rechazada_calidad', $supply->status);
        $this->assertSame(PedidoEtapa::Devuelta, $supply->etapa());
        $this->assertSame(
            'DEVUELTA POR COMPRAS: EL PRODUCTO NO CORRESPONDE A LA SEDE',
            $supply->comments()->latest('id')->value('body'),
        );
        Mail::assertSent(SupplyRequestReturnedMail::class, fn ($mail) => $mail->hasTo($this->requester->email));

        $product = SupplyProduct::query()->firstOrFail();

        $this->actingAs($this->requester)
            ->patch(route('supplies.update', ['module' => 'operaciones', 'supply_request' => $supply->id]), [
                'items' => [[
                    'type' => 'catalog',
                    'product_id' => $product->id,
                    'current_inventory' => 0,
                    'quantity' => 3,
                ]],
            ])
            ->assertRedirect(route('supplies.index', ['module' => 'operaciones']));

        $this->assertSame('pendiente_calidad', $supply->fresh()->status);
    }

    private function approvedPurchase(): PurchaseRequest
    {
        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => 501,
            'user_id' => $this->requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $this->director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
            'fecha_aprobacion' => now()->toDateString(),
        ]);

        $purchase->items()->create([
            'orden' => 1,
            'cantidad' => 2,
            'descripcion' => 'Monitor',
            'referencia' => 'N/A',
            'utilizacion' => 'Oficina',
            'ubicacion' => 'Cali',
        ]);

        return $purchase;
    }

    private function approvedSupply(): SupplyRequest
    {
        $product = SupplyProduct::query()->firstOrFail();
        $supply = SupplyRequest::query()->create([
            'user_id' => $this->requester->id,
            'area_key' => 'operaciones',
            'sede_id' => $this->requester->sede_id,
            'status' => 'aprobada_calidad',
        ]);

        $supply->items()->create([
            'supply_product_id' => $product->id,
            'current_inventory' => 0,
            'requested_quantity' => 4,
            'approved_quantity' => 4,
            'is_not_in_catalog' => false,
        ]);

        return $supply;
    }
}
