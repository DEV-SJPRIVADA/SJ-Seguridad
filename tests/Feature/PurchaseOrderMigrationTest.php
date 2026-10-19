<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderEvaluation;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica la estructura de `purchase_orders` (FEAT-047 / T4):
 * morph de origen, número OC = id y relaciones con ítems y evaluación.
 */
class PurchaseOrderMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_purchase_order_morph_and_numero_oc_work(): void
    {
        $source = $this->approvedPurchaseRequest();
        $provider = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR OC']);

        $order = PurchaseOrder::factory()->create([
            'source_type' => 'purchase_request',
            'source_id' => $source->getKey(),
            'provider_id' => $provider->getKey(),
            'provider_nombre' => $provider->nombre,
            'created_by' => $source->user_id,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->getKey(),
            'orden' => 1,
            'cantidad' => 2,
            'valor_unidad' => 150,
            'valor_total' => 300,
        ]);

        $order->refresh()->load(['source', 'items']);

        $this->assertSame($order->getKey(), $order->numeroOc);
        $this->assertInstanceOf(PurchaseRequest::class, $order->source);
        $this->assertSame($source->getKey(), $order->source->getKey());
        $this->assertCount(1, $order->items);
        $this->assertSame(300.0, (float) $order->items->first()->valor_total);
        $this->assertSame('Solicitud de compra', $order->sourceLabel());
    }

    public function test_purchase_order_evaluation_is_one_to_one(): void
    {
        $order = PurchaseOrder::factory()->create();

        $evaluation = PurchaseOrderEvaluation::factory()->create([
            'purchase_order_id' => $order->getKey(),
        ]);

        $order->refresh()->load('evaluation');

        $this->assertTrue($order->evaluation->is($evaluation));
        $this->assertSame($order->getKey(), $evaluation->purchase_order_id);
    }

    private function approvedPurchaseRequest(): PurchaseRequest
    {
        $requester = User::factory()->create();
        $approver = User::factory()->create();

        $request = PurchaseRequest::query()->create([
            'numero_solicitud' => 9001,
            'user_id' => $requester->getKey(),
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'SOLICITUD DE PRUEBA OC',
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $approver->getKey(),
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
            'asume_cliente' => false,
        ]);

        $request->items()->create([
            'orden' => 1,
            'cantidad' => 2,
            'descripcion' => 'CAMARA DE SEGURIDAD',
            'referencia' => 'REF-001',
            'utilizacion' => 'VIGILANCIA',
            'ubicacion' => 'BOGOTA',
        ]);

        return $request->load('items');
    }
}
