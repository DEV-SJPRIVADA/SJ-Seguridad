<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\AuditLog;
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
 * Export Excel del tablero Órdenes: una fila por producto con los datos de la OC, respetando filtros y búsqueda.
 */
class PurchaseOrderLinesExportTest extends TestCase
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

    public function test_index_shows_export_button_with_current_filters(): void
    {
        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.index', ['module' => 'compras', 'estado' => 'emitida']))
            ->assertOk()
            ->assertSee('id="orders-export-excel"', false)
            ->assertSee('ordenes-compra/exportar?estado=emitida', false);
    }

    public function test_export_has_one_row_per_product_with_order_data_and_respects_filters(): void
    {
        $alfa = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR ALFA']);
        $beta = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR BETA']);

        $purchase = $this->purchaseWithItems([
            ['cantidad' => 3, 'descripcion' => 'CAMARA', 'referencia' => 'CAM-1', 'marca' => 'HIKVISION'],
            ['cantidad' => 2, 'descripcion' => 'CABLE', 'referencia' => 'UTP-6', 'marca' => null],
        ]);

        $orderAlfa = app(PurchaseOrderService::class)->createFromPurchase($purchase, [
            'provider_id' => $alfa->id,
            'fecha' => '2026-10-10',
            'items' => [
                ['cantidad' => 3, 'descripcion' => 'CAMARA', 'valor_unidad' => 100, 'iva' => 19],
                ['cantidad' => 2, 'descripcion' => 'CABLE', 'valor_unidad' => 5, 'iva' => 0],
            ],
        ], $this->compras);

        $other = $this->purchaseWithItems([['cantidad' => 1, 'descripcion' => 'SILLA', 'referencia' => 'S-1', 'marca' => null]], 9902);
        $orderBeta = app(PurchaseOrderService::class)->createFromPurchase($other, [
            'provider_id' => $beta->id,
            'fecha' => '2026-10-11',
            'items' => [['cantidad' => 1, 'descripcion' => 'SILLA', 'valor_unidad' => 50, 'iva' => 0]],
        ], $this->compras);
        $orderBeta->update(['estado' => PurchaseOrder::ESTADO_ANULADA]);

        // Fila 1 título, fila 2 encabezados. Sin filtros: 3 filas de datos (2 líneas de la OC Alfa + 1 de la OC Beta).
        $sheet = $this->downloadSheet([]);
        $this->assertSame('N.º OC', (string) $sheet->getCell('A2')->getValue());
        $this->assertSame(5, $sheet->getHighestDataRow());

        $rows = $this->rowsByHeader($sheet);
        $alfaRows = array_values(array_filter($rows, fn (array $row): bool => (int) $row['N.º OC'] === $orderAlfa->id));
        $this->assertCount(2, $alfaRows);
        $this->assertSame('PROVEEDOR ALFA', $alfaRows[0]['Proveedor']);
        $this->assertSame('Emitida', $alfaRows[0]['Estado OC']);
        $this->assertSame('SC-'.str_pad((string) $purchase->numero_solicitud, 4, '0', STR_PAD_LEFT), $alfaRows[0]['Folio origen']);
        $this->assertSame('COT-ALFA', $alfaRows[0]['N.º cotización']);
        $this->assertSame('CAM-1', $alfaRows[0]['Artículo / referencia']);
        $this->assertSame('HIKVISION', $alfaRows[0]['Marca']);
        $this->assertEquals(319, $alfaRows[0]['Valor total línea']);
        $this->assertEquals(329, $alfaRows[0]['Total OC']);
        $this->assertSame('05/10/2026', $alfaRows[0]['Fecha solicitud']);
        $this->assertSame('07/10/2026', $alfaRows[0]['Fecha aprobación']);

        // Hoja 2: una fila por orden con su evaluación.
        $orderAlfa->evaluation()->create([
            'fecha_recibido' => '2026-10-15',
            'calif_referencias_cantidades' => 5,
            'calif_oportunidad_entrega' => 4,
            'calif_calidad_producto' => 5,
            'resultado_referencias' => 1.25,
            'resultado_oportunidad' => 1.0,
            'resultado_calidad' => 2.5,
            'puntaje_porcentaje' => 95,
            'umbral_codigo' => 'satisfactoria',
            'observaciones' => 'ENTREGA COMPLETA',
            'evaluated_by' => $this->compras->id,
        ]);

        $evaluation = $this->downloadSheet([], 'Evaluación');
        $evalRows = $this->rowsByHeader($evaluation);
        $this->assertCount(2, $evalRows);
        $alfaEval = collect($evalRows)->first(fn (array $row): bool => (int) $row['N.º OC'] === $orderAlfa->id);
        $this->assertNotNull($alfaEval, json_encode($evalRows));
        $this->assertSame('Sí', $alfaEval['Evaluada']);
        $this->assertSame('15/10/2026', $alfaEval['Fecha recibido']);
        $this->assertEquals(95, $alfaEval['Puntaje (%)']);
        $this->assertSame('Compra satisfactoria', $alfaEval['Resultado']);
        $this->assertSame($this->compras->name, $alfaEval['Evaluado por']);
        $this->assertSame('No', collect($evalRows)->first(fn (array $row): bool => (int) $row['N.º OC'] === $orderBeta->id)['Evaluada']);

        // Filtro de estado del panel: solo las 2 líneas de la OC emitida.
        $this->assertSame(4, $this->downloadSheet(['estado' => 'emitida'])->getHighestDataRow());
        // Proveedor y búsqueda del buscador (q): solo la línea de la OC Beta.
        $this->assertSame(3, $this->downloadSheet(['proveedor_id' => $beta->id])->getHighestDataRow());
        $this->assertSame(3, $this->downloadSheet(['q' => 'BETA'])->getHighestDataRow());
    }

    public function test_supply_order_takes_approval_date_from_quality_audit(): void
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $owner = User::factory()->create(['area_key' => 'operaciones', 'sede_id' => $site->id]);

        $supply = SupplyRequest::query()->create([
            'user_id' => $owner->id,
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'status' => 'aprobada_calidad',
        ]);
        $supply->forceFill(['created_at' => '2026-09-01 08:00:00'])->save();
        $supply->items()->create([
            'supply_product_id' => SupplyProduct::query()->firstOrFail()->id,
            'requested_quantity' => 2,
            'approved_quantity' => 2,
            'current_inventory' => 0,
            'is_not_in_catalog' => false,
        ]);

        AuditLog::query()->create([
            'module' => 'supplies',
            'event_type' => 'supply_request',
            'action' => 'quality_approve',
            'auditable_type' => SupplyRequest::class,
            'auditable_id' => $supply->id,
        ])->forceFill(['created_at' => '2026-09-03 10:00:00'])->save();

        app(PurchaseOrderService::class)->createFromSupply($supply->fresh('items'), [
            'provider_id' => PurchaseProvider::factory()->create()->id,
            'fecha' => '2026-09-04',
            'items' => [['cantidad' => 2, 'descripcion' => 'INSUMO', 'valor_unidad' => 10, 'iva' => 0]],
        ], $this->compras);

        $row = $this->rowsByHeader($this->downloadSheet([]))[0];
        $this->assertSame('01/09/2026', $row['Fecha solicitud']);
        $this->assertSame('03/09/2026', $row['Fecha aprobación']);
    }

    public function test_export_requires_processing_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->givePermissionTo('purchase.tab.my_requests');

        $this->actingAs($user)
            ->get(route('purchase-requests.orders.export', ['module' => 'compras']))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function downloadSheet(array $query, string $sheetName = 'Productos'): Worksheet
    {
        $response = $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.export', ['module' => 'compras', ...$query]));

        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'oc-export-').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $sheet = IOFactory::load($path)->getSheetByName($sheetName);
        $this->assertNotNull($sheet, "Falta la hoja {$sheetName}");

        return $sheet;
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

    /**
     * @param  list<array{cantidad: int, descripcion: string, referencia: string, marca: string|null}>  $items
     */
    private function purchaseWithItems(array $items, int $numero = 9901): PurchaseRequest
    {
        $requester = User::factory()->create(['area_key' => 'operaciones']);

        $purchase = PurchaseRequest::query()->create([
            'numero_solicitud' => $numero,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => '2026-10-05',
            'fecha_aprobacion' => '2026-10-07',
            'cantidad' => 1,
            'numero_cotizacion' => $numero === 9901 ? 'COT-ALFA' : null,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $requester->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
        ]);

        foreach ($items as $position => $item) {
            $purchase->items()->create([
                'orden' => $position + 1,
                ...$item,
                'utilizacion' => 'OFICINA',
                'ubicacion' => 'CALI',
            ]);
        }

        return $purchase->fresh('items');
    }
}
