<?php

declare(strict_types=1);

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseOrderExcelExporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-047 / T4 — Emisión de Órdenes de Compra (FO-AD-07).
 */
class PurchaseOrderEmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create(['area_key' => 'compras']);
        $this->compras->givePermissionTo('purchase.tab.processing');
    }

    public function test_compras_can_open_the_order_form_from_a_purchase_request(): void
    {
        $request = $this->approvedPurchaseRequest();
        $provider = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR UNO']);

        $response = $this->actingAs($this->compras)->get(route('purchase-requests.orders.purchase.create', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]));

        $response->assertOk();
        $response->assertSee('Emitir orden de compra');
        $response->assertSee('PROVEEDOR UNO');
    }

    public function test_compras_can_emit_an_order_and_the_provider_snapshot_is_frozen(): void
    {
        $request = $this->approvedPurchaseRequest(2, 1);
        $provider = PurchaseProvider::factory()->create([
            'nombre' => 'PROVEEDOR SNAPSHOT',
            'identificacion' => '900123456-1',
            'ciudad' => 'MEDELLIN',
            'dias_pago' => 45,
            'forma_pago' => 'CREDITO',
        ]);

        $response = $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), [
            'provider_id' => $provider->getKey(),
            'fecha' => now()->toDateString(),
            'ciudad' => 'MEDELLIN',
            'observaciones' => 'Entrega en sitio principal',
            'items' => [
                [
                    'cantidad' => 2,
                    'articulo' => 'REF-001',
                    'descripcion' => 'CAMARA DE SEGURIDAD',
                    'valor_unidad' => 150.50,
                    'iva' => 20,
                ],
                [
                    'cantidad' => 1,
                    'articulo' => 'REF-002',
                    'descripcion' => 'CABLE DE RED',
                    'valor_unidad' => 100,
                    'iva' => 0,
                ],
            ],
        ]);

        $order = PurchaseOrder::query()->latest('id')->first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('purchase-requests.orders.show', [
            'module' => 'compras',
            'purchase_order' => $order->getKey(),
        ]));

        $this->assertSame('purchase_request', $order->source_type);
        $this->assertSame($request->getKey(), (int) $order->source_id);
        $this->assertSame('PROVEEDOR SNAPSHOT', $order->provider_nombre);
        $this->assertSame('900123456-1', $order->provider_identificacion);
        $this->assertSame('MEDELLIN', $order->provider_ciudad);
        $this->assertSame(45, (int) $order->provider_dias_pago);
        $this->assertSame('emitida', $order->estado);
        $this->assertCount(2, $order->items);
        // 2 * 150.50 + 20 + 1 * 100 = 421.00
        $this->assertSame(421.0, (float) $order->total);
        $this->assertSame($order->getKey(), $order->numeroOc);
    }

    public function test_order_can_be_emitted_from_a_supply_request_using_authorized_quantities(): void
    {
        $supplyRequest = $this->approvedSupplyRequest();
        $provider = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR SUMINISTROS']);

        $response = $this->actingAs($this->compras)->post(route('purchase-requests.orders.supply.store', [
            'module' => 'compras',
            'supply_request' => $supplyRequest->getKey(),
        ]), [
            'provider_id' => $provider->getKey(),
            'fecha' => now()->toDateString(),
            'items' => [
                [
                    'cantidad' => 4,
                    'articulo' => 'REF-SUM',
                    'descripcion' => 'PAPELERIA',
                    'valor_unidad' => 25,
                    'iva' => 0,
                ],
            ],
        ]);

        $order = PurchaseOrder::query()->latest('id')->first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('purchase-requests.orders.show', [
            'module' => 'compras',
            'purchase_order' => $order->getKey(),
        ]));

        $this->assertSame('supply_request', $order->source_type);
        $this->assertSame($supplyRequest->getKey(), (int) $order->source_id);
        $this->assertSame('PROVEEDOR SUMINISTROS', $order->provider_nombre);
        $this->assertSame(100.0, (float) $order->total);
    }

    public function test_users_without_processing_permission_cannot_emit_orders(): void
    {
        $request = $this->approvedPurchaseRequest();
        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), [
            'provider_id' => $provider->getKey(),
            'fecha' => now()->toDateString(),
            'items' => [
                ['cantidad' => 1, 'descripcion' => 'X', 'valor_unidad' => 10, 'iva' => 0],
            ],
        ])->assertRedirect();

        $visitor = User::factory()->create();
        $visitor->givePermissionTo('purchase.providers.view');

        $this->actingAs($visitor)
            ->get(route('purchase-requests.orders.purchase.create', [
                'module' => 'compras',
                'purchase_request' => $request->getKey(),
            ]))
            ->assertForbidden();
    }

    public function test_inactive_providers_cannot_be_selected(): void
    {
        $request = $this->approvedPurchaseRequest();
        $provider = PurchaseProvider::factory()->inactivo()->create();

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.purchase.store', [
                'module' => 'compras',
                'purchase_request' => $request->getKey(),
            ]), [
                'provider_id' => $provider->getKey(),
                'fecha' => now()->toDateString(),
                'items' => [
                    ['cantidad' => 1, 'descripcion' => 'X', 'valor_unidad' => 10, 'iva' => 0],
                ],
            ])
            ->assertSessionHasErrors('provider_id');

        $this->assertSame(0, PurchaseOrder::query()->count());
    }

    public function test_emitted_order_shows_the_snapshot_and_can_be_downloaded(): void
    {
        $request = $this->approvedPurchaseRequest();
        $provider = PurchaseProvider::factory()->create([
            'nombre' => 'PROVEEDOR DESCARGABLE',
            'identificacion' => '900555555-1',
        ]);

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), [
            'provider_id' => $provider->getKey(),
            'fecha' => now()->toDateString(),
            'items' => [
                ['cantidad' => 2, 'articulo' => 'REF-001', 'descripcion' => 'CAMARA', 'valor_unidad' => 100, 'iva' => 0],
            ],
        ])->assertRedirect();

        $order = PurchaseOrder::query()->latest('id')->firstOrFail();

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.show', [
                'module' => 'compras',
                'purchase_order' => $order->getKey(),
            ]))
            ->assertOk()
            ->assertSee('PROVEEDOR DESCARGABLE')
            ->assertSee('Orden de compra N.º '.$order->numeroOc);

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.pdf', [
                'module' => 'compras',
                'purchase_order' => $order->getKey(),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.excel', [
                'module' => 'compras',
                'purchase_order' => $order->getKey(),
            ]))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    }

    public function test_excel_export_contains_the_order_data(): void
    {
        $request = $this->approvedPurchaseRequest();
        $provider = PurchaseProvider::factory()->create([
            'nombre' => 'PROVEEDOR EXCEL',
            'identificacion' => '900777777-1',
        ]);

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), [
            'provider_id' => $provider->getKey(),
            'fecha' => now()->toDateString(),
            'items' => [
                ['cantidad' => 2, 'articulo' => 'REF-001', 'descripcion' => 'CAMARA', 'valor_unidad' => 100, 'iva' => 10],
            ],
        ])->assertRedirect();

        $order = PurchaseOrder::query()->firstOrFail();

        $spreadsheet = app(PurchaseOrderExcelExporter::class)->buildSpreadsheet($order);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Orden de compra', $sheet->getTitle());
        $this->assertSame('ORDEN DE COMPRA O SERVICIO', $sheet->getCell('B1')->getValue());
        $this->assertSame('FO-AD-07', $sheet->getCell('G1')->getValue());
        $this->assertSame('Enero de 2025', $sheet->getCell('G2')->getValue());
        $this->assertSame('OC No.', $sheet->getCell('F5')->getValue());
        $this->assertSame($order->numeroOc, $sheet->getCell('G5')->getValue());
        $this->assertSame($order->provider_nombre, $sheet->getCell('B8')->getValue());
        $this->assertSame($order->provider_identificacion, $sheet->getCell('B9')->getValue());
        $this->assertSame('Forma de pago', $sheet->getCell('A15')->getValue());
        $this->assertSame('Valor total', $sheet->getCell('G22')->getValue());
        $this->assertSame(210.0, (float) $sheet->getCell('G24')->getValue());
    }

    public function test_the_same_request_can_be_split_between_several_providers(): void
    {
        $request = $this->approvedPurchaseRequest(100);
        $providerA = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR A']);
        $providerB = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR B']);
        $providerC = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR C']);

        // 50 al proveedor A
        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($providerA, 50))->assertRedirect()->assertSessionHasNoErrors();

        // 20 al proveedor B
        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($providerB, 20))->assertRedirect()->assertSessionHasNoErrors();

        // 30 al proveedor C
        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($providerC, 30))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(3, PurchaseOrder::query()->count());
        $this->assertSame([50.0, 20.0, 30.0], PurchaseOrderItem::query()
            ->orderBy('id')
            ->get()
            ->map(fn (PurchaseOrderItem $item): float => (float) $item->cantidad)
            ->all());

        // Ya no queda saldo: una cuarta orden debe rechazarse
        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($providerA, 1))
            ->assertSessionHasErrors('items.0.cantidad');

        $this->assertSame(3, PurchaseOrder::query()->count());
    }

    public function test_a_duplicated_order_is_rejected_when_there_is_no_pending_quantity(): void
    {
        $request = $this->approvedPurchaseRequest(100);
        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($provider, 100))->assertRedirect();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($provider, 100))
            ->assertSessionHasErrors('items.0.cantidad');

        $this->assertSame(1, PurchaseOrder::query()->count());
    }

    public function test_the_order_form_shows_the_pending_quantity(): void
    {
        $request = $this->approvedPurchaseRequest(100);
        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($provider, 40))->assertRedirect();

        $response = $this->actingAs($this->compras)->get(route('purchase-requests.orders.purchase.create', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]));

        $response->assertOk();
        $response->assertSee('60.00');
        $response->assertSee('ya tiene 1 orden de compra emitida');
    }

    public function test_the_orders_list_shows_the_generated_orders(): void
    {
        $request = $this->approvedPurchaseRequest(10);
        $provider = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR LISTADO']);

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($provider, 10))->assertRedirect();

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.index', ['module' => 'compras']))
            ->assertOk();

        $response = $this->actingAs($this->compras)
            ->getJson(route('purchase-requests.orders.datatable', ['module' => 'compras']));

        $response->assertOk();
        $response->assertJsonPath('recordsTotal', 1);
        $response->assertJsonPath('recordsFiltered', 1);
        $response->assertJsonPath('data.0.2', 'PROVEEDOR LISTADO');
    }

    public function test_the_orders_list_can_be_filtered(): void
    {
        $request = $this->approvedPurchaseRequest(10);
        $provider = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR FILTRO']);

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]), $this->payload($provider, 10))->assertRedirect();

        $supplyRequest = $this->approvedSupplyRequest();
        $this->actingAs($this->compras)->post(route('purchase-requests.orders.supply.store', [
            'module' => 'compras',
            'supply_request' => $supplyRequest->getKey(),
        ]), [
            'provider_id' => $provider->getKey(),
            'fecha' => now()->toDateString(),
            'items' => [
                ['cantidad' => 4, 'articulo' => 'REF-SUM', 'descripcion' => 'PAPELERIA', 'valor_unidad' => 25, 'iva' => 0],
            ],
        ])->assertRedirect();

        $this->actingAs($this->compras)
            ->getJson(route('purchase-requests.orders.datatable', ['module' => 'compras']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);

        $this->actingAs($this->compras)
            ->getJson(route('purchase-requests.orders.datatable', ['module' => 'compras', 'tipo' => 'suministro']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($this->compras)
            ->getJson(route('purchase-requests.orders.datatable', ['module' => 'compras', 'estado' => 'borrador']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 0);
    }

    public function test_the_request_detail_links_to_the_order_form_for_compras(): void
    {
        $request = $this->approvedPurchaseRequest();
        $url = route('purchase-requests.orders.purchase.create', [
            'module' => 'compras',
            'purchase_request' => $request->getKey(),
        ]);

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.show', [
                'module' => 'compras',
                'purchase_request' => $request->getKey(),
                'from' => 'processing',
            ]))
            ->assertOk()
            ->assertSee('Emitir orden de compra')
            ->assertSee($url, false);

        // El solicitante ve el detalle pero no puede emitir OC: no debe aparecer el botón.
        $this->actingAs($request->user)
            ->get(route('purchase-requests.show', [
                'module' => 'compras',
                'purchase_request' => $request->getKey(),
                'from' => 'processing',
            ]))
            ->assertOk()
            ->assertDontSee($url, false);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(PurchaseProvider $provider, float $cantidad): array
    {
        return [
            'provider_id' => $provider->getKey(),
            'fecha' => now()->toDateString(),
            'items' => [
                [
                    'cantidad' => $cantidad,
                    'articulo' => 'REF-001',
                    'descripcion' => 'CAMARA DE SEGURIDAD',
                    'valor_unidad' => 100,
                    'iva' => 0,
                ],
            ],
        ];
    }

    private function approvedPurchaseRequest(int ...$cantidades): PurchaseRequest
    {
        $cantidades = $cantidades === [] ? [2] : $cantidades;

        $requester = User::factory()->create(['area_key' => 'operaciones']);
        $approver = User::factory()->create();

        $request = PurchaseRequest::query()->create([
            'numero_solicitud' => 9100,
            'user_id' => $requester->getKey(),
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'SOLICITUD PARA EMITIR OC',
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $approver->getKey(),
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
            'asume_cliente' => false,
        ]);

        foreach ($cantidades as $position => $cantidad) {
            $request->items()->create([
                'orden' => $position + 1,
                'cantidad' => $cantidad,
                'descripcion' => 'CAMARA DE SEGURIDAD',
                'referencia' => 'REF-00'.($position + 1),
                'utilizacion' => 'VIGILANCIA',
                'ubicacion' => 'BOGOTA',
            ]);
        }

        return $request->load('items');
    }

    private function approvedSupplyRequest(): SupplyRequest
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $requester = User::factory()->create(['area_key' => 'operaciones', 'sede_id' => $site->getKey()]);

        $request = SupplyRequest::query()->create([
            'user_id' => $requester->getKey(),
            'area_key' => 'operaciones',
            'sede_id' => $site->getKey(),
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'en_compras',
        ]);

        $request->items()->create([
            'supply_product_id' => $product->getKey(),
            'requested_quantity' => 10,
            'approved_quantity' => 4,
            'unit_cost' => 25,
            'is_not_in_catalog' => false,
        ]);

        return $request->load('items.product');
    }
}
