<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
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

        // Filtro de estado del panel: solo las 2 líneas de la OC emitida.
        $this->assertSame(4, $this->downloadSheet(['estado' => 'emitida'])->getHighestDataRow());
        // Proveedor y búsqueda del buscador (q): solo la línea de la OC Beta.
        $this->assertSame(3, $this->downloadSheet(['proveedor_id' => $beta->id])->getHighestDataRow());
        $this->assertSame(3, $this->downloadSheet(['q' => 'BETA'])->getHighestDataRow());
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
    private function downloadSheet(array $query): Worksheet
    {
        $response = $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.export', ['module' => 'compras', ...$query]));

        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'oc-export-').'.xlsx';
        file_put_contents($path, $response->streamedContent());

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
            'fecha_solicitud' => now()->toDateString(),
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
