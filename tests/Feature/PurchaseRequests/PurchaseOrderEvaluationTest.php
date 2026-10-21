<?php

declare(strict_types=1);

namespace Tests\Feature\PurchaseRequests;

use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderEvaluation;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseFlowStateService;
use App\Services\PurchaseRequests\PurchaseOrderExcelExporter;
use App\Services\PurchaseRequests\PurchaseOrderPdfExporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-048 / T5 — fecha de recibido, evaluación 25/25/50 y cierre con fechas.
 */
class PurchaseOrderEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    private User $editor;

    private int $folio = 8800;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $this->compras->givePermissionTo('purchase.tab.processing');

        $this->editor = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $this->editor->givePermissionTo('purchase.providers.edit');
    }

    public function test_processing_or_view_without_edit_cannot_record_a_receipt(): void
    {
        $order = $this->emit($this->approvedPurchase(2), 2);
        $viewer = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $viewer->givePermissionTo('purchase.providers.view');

        $this->actingAs($this->compras)
            ->get($this->evaluationRoute($order))
            ->assertForbidden();

        $this->actingAs($this->compras)
            ->put($this->evaluationRoute($order), $this->evaluationPayload())
            ->assertForbidden();

        $this->actingAs($viewer)
            ->put($this->evaluationRoute($order), $this->evaluationPayload())
            ->assertForbidden();

        $this->assertSame(0, PurchaseOrderEvaluation::query()->count());
        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $order->source->fresh()->estado_compras);
    }

    public function test_the_receipt_date_can_be_saved_without_ratings_and_observations_stay_uppercase(): void
    {
        $purchase = $this->approvedPurchase(2);
        $order = $this->emit($purchase, 2);

        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $purchase->fresh()->estado_compras);

        $this->actingAs($this->editor)
            ->put($this->evaluationRoute($order), [
                'fecha_recibido' => '2026-10-18',
                'observaciones' => 'llegó completo',
            ])
            ->assertRedirect($this->evaluationRoute($order))
            ->assertSessionHas('status');

        $evaluation = $order->fresh()->evaluation;

        $this->assertNotNull($evaluation);
        $this->assertSame('2026-10-18', $evaluation->fecha_recibido?->toDateString());
        $this->assertNull($evaluation->calif_referencias_cantidades);
        $this->assertNull($evaluation->calif_oportunidad_entrega);
        $this->assertNull($evaluation->calif_calidad_producto);
        $this->assertNull($evaluation->puntaje_porcentaje);
        $this->assertNull($evaluation->umbral_codigo);
        $this->assertSame('LLEGÓ COMPLETO', $evaluation->observaciones);
        $this->assertSame($this->editor->id, $evaluation->evaluated_by);
        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $purchase->fresh()->estado_compras);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'purchase_requests',
            'area' => 'compras',
            'event_type' => 'purchase_order_evaluation',
            'action' => 'created',
            'auditable_type' => PurchaseOrderEvaluation::class,
            'auditable_id' => $evaluation->id,
        ]);
        $this->assertSame(1, AuditLog::query()->where('event_type', 'purchase_order_evaluation')->count());
    }

    public function test_ratings_use_the_configured_weights_and_thresholds(): void
    {
        $cases = [
            [5, 5, 5, 1.25, 2.5, 100.0, 'satisfactoria'],
            [4, 4, 4, 1.0, 2.0, 80.0, 'satisfactoria'],
            [3, 3, 3, 0.75, 1.5, 60.0, 'seguimiento'],
            [1, 1, 1, 0.25, 0.5, 20.0, 'insatisfactoria'],
        ];

        foreach ($cases as [$referencias, $oportunidad, $calidad, $resultadoRef, $resultadoCal, $puntaje, $umbral]) {
            $order = $this->emit($this->approvedPurchase(1), 1);

            $this->actingAs($this->editor)->put($this->evaluationRoute($order), [
                'fecha_recibido' => '2026-10-18',
                'calif_referencias_cantidades' => $referencias,
                'calif_oportunidad_entrega' => $oportunidad,
                'calif_calidad_producto' => $calidad,
            ])->assertRedirect();

            $evaluation = $order->fresh()->evaluation;

            $this->assertNotNull($evaluation);
            $this->assertEquals($resultadoRef, (float) $evaluation->resultado_referencias);
            $this->assertEquals($resultadoRef, (float) $evaluation->resultado_oportunidad);
            $this->assertEquals($resultadoCal, (float) $evaluation->resultado_calidad);
            $this->assertEquals($puntaje, (float) $evaluation->puntaje_porcentaje);
            $this->assertSame($umbral, $evaluation->umbral_codigo);
        }
    }

    public function test_an_evaluation_is_editable_and_a_partial_rating_is_rejected(): void
    {
        $order = $this->emit($this->approvedPurchase(1), 1);

        $this->actingAs($this->editor)->put($this->evaluationRoute($order), [
            'fecha_recibido' => '2026-10-18',
            'calif_referencias_cantidades' => 5,
        ])->assertSessionHasErrors('calif_oportunidad_entrega');

        $this->assertNull($order->fresh()->evaluation);

        $this->actingAs($this->editor)->put($this->evaluationRoute($order), [
            'fecha_recibido' => '2026-10-18',
            'calif_referencias_cantidades' => 5,
            'calif_oportunidad_entrega' => 5,
            'calif_calidad_producto' => 5,
            'observaciones' => 'primera',
        ])->assertRedirect();

        $this->actingAs($this->editor)->put($this->evaluationRoute($order), [
            'fecha_recibido' => '2026-10-19',
            'observaciones' => 'solo la fecha',
        ])->assertRedirect();

        $evaluation = $order->fresh()->evaluation;

        $this->assertSame(1, $order->evaluation()->count());
        $this->assertSame('2026-10-19', $evaluation?->fecha_recibido?->toDateString());
        $this->assertNull($evaluation?->puntaje_porcentaje);
        $this->assertNull($evaluation?->calif_calidad_producto);
        $this->assertSame('SOLO LA FECHA', $evaluation?->observaciones);

        $this->actingAs($this->editor)->put($this->evaluationRoute($order), [
            'fecha_recibido' => '2026-10-19',
            'calif_referencias_cantidades' => 1,
            'calif_oportunidad_entrega' => 1,
            'calif_calidad_producto' => 1,
        ])->assertRedirect();

        $this->assertSame(1, $order->evaluation()->count());
        $this->assertEquals(20.0, (float) $order->fresh()->evaluation?->puntaje_porcentaje);
        $this->assertSame('insatisfactoria', $order->fresh()->evaluation?->umbral_codigo);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'purchase_order_evaluation',
            'action' => 'updated',
            'auditable_id' => $order->evaluation()->value('id'),
        ]);
    }

    public function test_zero_balance_closes_only_when_every_order_has_a_receipt_date(): void
    {
        $purchase = $this->approvedPurchase(4);
        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), $this->orderPayload($provider->id, 2))->assertRedirect();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), $this->orderPayload($provider->id, 2))->assertRedirect();

        $orders = PurchaseOrder::query()->where('source_id', $purchase->id)->orderBy('id')->get();

        $this->assertCount(2, $orders);
        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $purchase->fresh()->estado_compras);

        $this->actingAs($this->editor)
            ->put($this->evaluationRoute($orders[0]), $this->evaluationPayload())
            ->assertRedirect();

        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $purchase->fresh()->estado_compras);

        $this->actingAs($this->editor)
            ->put($this->evaluationRoute($orders[1]), $this->evaluationPayload())
            ->assertRedirect();

        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $purchase->fresh()->estado_compras);
    }

    public function test_a_supply_with_zero_balance_closes_when_the_receipt_date_is_saved(): void
    {
        $supply = $this->approvedSupply(2);
        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.supply.store', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]), $this->orderPayload($provider->id, 2))->assertRedirect();

        $this->assertSame('en_compras', $supply->fresh()->status);

        $order = PurchaseOrder::query()
            ->where('source_type', 'supply_request')
            ->where('source_id', $supply->id)
            ->firstOrFail();

        $this->actingAs($this->editor)
            ->put($this->evaluationRoute($order), $this->evaluationPayload())
            ->assertRedirect();

        $this->assertSame('completada', $supply->fresh()->status);
    }

    public function test_close_balance_still_closes_without_receipt_dates_and_a_closed_order_is_not_reopened(): void
    {
        $purchase = $this->approvedPurchase(4);
        $this->emit($purchase, 4);

        $this->assertSame(PurchaseRequest::COMPRAS_EN_CURSO, $purchase->fresh()->estado_compras);

        $this->actingAs($this->compras)->post(route('purchase-requests.processing.purchase.close', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), [
            'motivo' => 'Se cierra sin recibido',
        ])->assertRedirect();

        $purchase->refresh();

        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $purchase->estado_compras);
        $this->assertSame('SE CIERRA SIN RECIBIDO', $purchase->comments()->value('body'));
        $this->assertSame(0, PurchaseOrderEvaluation::query()->count());

        app(PurchaseFlowStateService::class)->sync($purchase->fresh());

        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $purchase->fresh()->estado_compras);
    }

    public function test_a_purchase_already_closed_without_dates_stays_closed(): void
    {
        $purchase = $this->approvedPurchase(2);
        $this->emit($purchase, 2);
        $purchase->update(['estado_compras' => PurchaseRequest::COMPRAS_COMPLETADO]);

        app(PurchaseFlowStateService::class)->sync($purchase->fresh());

        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $purchase->fresh()->estado_compras);
    }

    public function test_the_order_document_does_not_print_the_evaluation(): void
    {
        $order = $this->emit($this->approvedPurchase(1), 1);

        $this->actingAs($this->editor)->put($this->evaluationRoute($order), [
            'fecha_recibido' => '2026-10-18',
            'calif_referencias_cantidades' => 5,
            'calif_oportunidad_entrega' => 5,
            'calif_calidad_producto' => 5,
            'observaciones' => 'nota interna de evaluación',
        ])->assertRedirect();

        $order = $order->fresh();
        $html = mb_strtolower(app(PurchaseOrderPdfExporter::class)->html($order));
        $excel = mb_strtolower((string) json_encode(
            app(PurchaseOrderExcelExporter::class)->buildSpreadsheet($order)->getActiveSheet()->toArray(),
            JSON_UNESCAPED_UNICODE,
        ));

        $this->assertStringContainsString('orden de compra o servicio', $html);
        $this->assertStringNotContainsString('puntaje', $html);
        $this->assertStringNotContainsString('satisfactoria', $html);
        $this->assertStringNotContainsString('nota interna de evaluación', $html);
        $this->assertStringNotContainsString('puntaje', $excel);
        $this->assertStringNotContainsString('satisfactoria', $excel);
    }

    public function test_the_order_detail_links_to_the_evaluation_only_with_edit_permission(): void
    {
        $order = $this->emit($this->approvedPurchase(1), 1);
        $show = route('purchase-requests.orders.show', [
            'module' => 'compras',
            'purchase_order' => $order->id,
        ]);

        $this->actingAs($this->compras)
            ->get($show)
            ->assertOk()
            ->assertDontSee('Evaluar entrega')
            ->assertSee('Recibido:');

        $this->compras->givePermissionTo('purchase.providers.edit');

        $this->actingAs($this->compras)
            ->get($show)
            ->assertOk()
            ->assertSee('Evaluar entrega');

        $this->actingAs($this->editor)
            ->get($this->evaluationRoute($order))
            ->assertOk()
            ->assertSee('Fecha de recibido')
            ->assertSee('Calidad del producto')
            ->assertSee('Guardar evaluación');
    }

    private function approvedPurchase(int $cantidad): PurchaseRequest
    {
        $this->folio++;
        $requester = User::factory()->create(['area_key' => 'operaciones', 'must_change_password' => false]);
        $director = User::factory()->create(['must_change_password' => false]);

        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => $this->folio,
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

    private function approvedSupply(int $cantidad): SupplyRequest
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $owner = User::factory()->create([
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
            'status' => 'aprobada_calidad',
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

    private function emit(PurchaseRequest $purchase, float $cantidad): PurchaseOrder
    {
        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $purchase->id,
        ]), $this->orderPayload($provider->id, $cantidad))->assertRedirect();

        return PurchaseOrder::query()
            ->where('source_type', 'purchase_request')
            ->where('source_id', $purchase->id)
            ->latest('id')
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(int $providerId, float $cantidad): array
    {
        return [
            'provider_id' => $providerId,
            'fecha' => now()->toDateString(),
            'items' => [[
                'cantidad' => $cantidad,
                'articulo' => 'REF-OC',
                'descripcion' => 'CAMARA',
                'valor_unidad' => 10,
                'iva' => 0,
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluationPayload(): array
    {
        return [
            'fecha_recibido' => '2026-10-18',
        ];
    }

    private function evaluationRoute(PurchaseOrder $order): string
    {
        return route('purchase-requests.orders.evaluation.update', [
            'module' => 'compras',
            'purchase_order' => $order->id,
        ]);
    }
}
