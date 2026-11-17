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
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

/**
 * Mis solicitudes muestra y exporta el total de OC autorizadas del solicitante.
 */
class MyRequestsOrderTotalsTest extends TestCase
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

    public function test_requester_sees_authorized_order_totals_and_exports_own_lines(): void
    {
        [$requester, , $purchase] = $this->purchaseScenario();
        $authorized = $this->emitPurchaseOrder($purchase, 2, 150);
        $this->authorizeOrder($authorized);

        $pending = $this->emitPurchaseOrder($purchase, 1, 80);
        $this->assertSame(PurchaseOrder::ESTADO_PENDIENTE_APROBACION, $pending->estado);

        $annulled = $this->emitPurchaseOrder($purchase, 1, 40);
        $this->authorizeOrder($annulled);
        $annulled->update(['estado' => PurchaseOrder::ESTADO_ANULADA]);

        [$otherRequester, , $otherPurchase] = $this->purchaseScenario(8802, 'otro.director@test.local');
        $otherOrder = $this->emitPurchaseOrder($otherPurchase, 1, 999);
        $this->authorizeOrder($otherOrder);

        $this->actingAs($requester)
            ->get(route('purchase-requests.index', ['module' => 'compras']))
            ->assertOk()
            ->assertSee('Total OC: $ 300,00', false)
            ->assertSee('>1</td>', false)
            ->assertSee('Exportar Excel de las solicitudes filtradas', false)
            ->assertDontSee('$ 999,00')
            ->assertDontSee('$ 80,00')
            ->assertDontSee('$ 40,00');

        $sheet = $this->downloadPurchaseSheet($requester);
        $rows = $this->rowsByHeader($sheet);
        $this->assertCount(1, $rows);
        $this->assertSame('SC-8801', $rows[0]['Folio solicitud']);
        $this->assertSame(300.0, (float) $rows[0]['Valor total línea']);
        $this->assertSame(300.0, (float) $rows[0]['Total OC']);

        $this->actingAs($otherRequester)
            ->get(route('purchase-requests.export', ['module' => 'compras']))
            ->assertOk();

        $otherSheet = $this->downloadPurchaseSheet($otherRequester);
        $otherRows = $this->rowsByHeader($otherSheet);
        $this->assertCount(1, $otherRows);
        $this->assertSame('SC-8802', $otherRows[0]['Folio solicitud']);
        $this->assertSame(999.0, (float) $otherRows[0]['Valor total línea']);
    }

    public function test_purchase_export_respects_list_filters(): void
    {
        [$requester, $director] = $this->purchaseScenario();

        $inRange = PurchaseRequest::query()->create([
            'numero_solicitud' => 12,
            'user_id' => $requester->id,
            'area_key' => 'compras',
            'fecha_solicitud' => '2026-03-15',
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
        ]);
        $inRange->items()->create([
            'orden' => 1,
            'cantidad' => 1,
            'descripcion' => 'SILLA',
            'referencia' => 'S-1',
            'utilizacion' => 'OFICINA',
            'ubicacion' => 'CALI',
        ]);
        $orderInRange = $this->emitPurchaseOrder($inRange->fresh('items'), 1, 50);
        $this->authorizeOrder($orderInRange);

        $this->actingAs($requester)
            ->get(route('purchase-requests.index', [
                'module' => 'compras',
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
            ]))
            ->assertOk()
            ->assertSee('Total OC: $ 50,00', false)
            ->assertSee('SC-0012')
            ->assertDontSee('SC-8801');

        $rows = $this->rowsByHeader($this->downloadPurchaseSheet($requester, [
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ]));
        $this->assertCount(1, $rows);
        $this->assertSame('SC-0012', $rows[0]['Folio solicitud']);
    }

    public function test_supply_requester_sees_authorized_totals_and_does_not_export_others(): void
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $owner = User::factory()->create([
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);
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
        $this->authorizeOrder($order);

        $pending = app(PurchaseOrderService::class)->createFromSupply($supply->fresh('items'), [
            'provider_id' => PurchaseProvider::factory()->create()->id,
            'fecha' => now()->toDateString(),
            'items' => [['cantidad' => 1, 'descripcion' => 'PENDIENTE', 'valor_unidad' => 7, 'iva' => 0]],
        ], $this->compras);
        $this->assertSame(PurchaseOrder::ESTADO_PENDIENTE_APROBACION, $pending->estado);

        [, , $purchase] = $this->purchaseScenario(8803, 'tercer.director@test.local');
        $purchaseOrder = $this->emitPurchaseOrder($purchase, 1, 50);
        $this->authorizeOrder($purchaseOrder);

        $this->actingAs($owner)
            ->get(route('supplies.index', ['module' => 'operaciones']))
            ->assertOk()
            ->assertSee('Total OC: $ 30,00', false)
            ->assertDontSee('$ 50,00')
            ->assertDontSee('$ 7,00');

        $sheet = $this->downloadSupplySheet($owner);
        $rows = $this->rowsByHeader($sheet);
        $this->assertCount(1, $rows);
        $this->assertSame($supply->folioVisible(), $rows[0]['Folio solicitud']);
        $this->assertSame('INSUMO', $rows[0]['Descripción']);
        $this->assertSame(30.0, (float) $rows[0]['Valor total línea']);
    }

    public function test_my_requests_export_is_forbidden_without_tab_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('purchase-requests.export', ['module' => 'compras']))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('supplies.export', ['module' => 'operaciones']))
            ->assertForbidden();
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
            'fecha_solicitud' => '2026-01-10',
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

    private function authorizeOrder(PurchaseOrder $order): PurchaseOrder
    {
        $order->update([
            'estado' => PurchaseOrder::ESTADO_EMITIDA,
            'aprobacion_estado' => PurchaseOrder::APROBACION_APROBADA,
        ]);

        return $order->fresh();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function downloadPurchaseSheet(User $user, array $query = []): Worksheet
    {
        $response = $this->actingAs($user)
            ->get(route('purchase-requests.export', ['module' => 'compras', ...$query]));

        $response->assertOk();

        return $this->sheetFromResponse($response->streamedContent());
    }

    private function downloadSupplySheet(User $user): Worksheet
    {
        $response = $this->actingAs($user)
            ->get(route('supplies.export', ['module' => 'operaciones']));

        $response->assertOk();

        return $this->sheetFromResponse($response->streamedContent());
    }

    private function sheetFromResponse(string $content): Worksheet
    {
        $path = tempnam(sys_get_temp_dir(), 'my-req-export-').'.xlsx';
        file_put_contents($path, $content);

        return IOFactory::load($path)->getActiveSheet();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsByHeader(Worksheet $sheet): array
    {
        $data = $sheet->toArray(null, true, false, false);
        $headers = $data[1];
        $rows = [];

        foreach (array_slice($data, 2) as $line) {
            $rows[] = array_combine($headers, $line);
        }

        return $rows;
    }
}
