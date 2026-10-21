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
use App\Services\PurchaseRequests\PurchaseOrderPdfExporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

/**
 * FEAT-048 / T1 — Comodato y Activo, snapshot de la OC y FO-AD-07 oficial.
 */
class PurchaseOrderFoAd07Test extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create([
            'name' => 'ANALISTA COMPRAS',
            'area_key' => 'compras',
        ]);
        $this->compras->givePermissionTo('purchase.tab.processing');
    }

    public function test_condiciones_match_the_analyst_text_exactly(): void
    {
        $this->assertSame($this->legalText(), $this->normalizeNewlines((string) config('purchase-orders.condiciones')));
        $this->assertSame('ORDEN DE COMPRA O SERVICIO', config('purchase-orders.report_title'));
        $this->assertSame('Enero de 2025', config('purchase-orders.form_period'));
        $this->assertStringContainsString('ORDEN  SERA', config('purchase-orders.condiciones'));
        $this->assertStringContainsString('ALMACEN;  ESTA', config('purchase-orders.condiciones'));
        $this->assertStringContainsString('CONTABLES  SOLO', config('purchase-orders.condiciones'));
        $this->assertStringContainsString('RELACIÓN COMERCIAL', config('purchase-orders.condiciones'));
    }

    public function test_cliente_persists_boolean_flags_and_the_order_prints_si_or_no(): void
    {
        Mail::fake();

        $director = $this->director('DIRECTOR AREA');
        $requester = $this->requester();

        $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Cliente',
            'razon_social' => 'cliente andino',
            'aprobador_id' => $director->id,
            'proyecto_nuevo' => '1',
            'asume_cliente' => '0',
            'reinversion' => '1',
            'comodato' => '0',
            'activo' => '1',
            'items' => [[
                'cantidad' => 2,
                'descripcion' => 'camara de seguridad',
                'referencia' => 'REF-001',
                'utilizacion' => 'vigilancia',
                'ubicacion' => 'Cali',
            ]],
        ])->assertRedirect();

        $request = PurchaseRequest::query()->latest('id')->firstOrFail();
        $request->refresh();

        $this->assertTrue($request->proyecto_nuevo);
        $this->assertFalse($request->asume_cliente);
        $this->assertTrue($request->reinversion);
        $this->assertFalse($request->comodato);
        $this->assertTrue($request->activo);
        $this->assertIsBool($request->comodato);
        $this->assertIsBool($request->activo);

        $raw = DB::table('purchase_requests')->where('id', $request->id)->first();
        $this->assertTrue(in_array($raw->comodato, [0, '0', false], true));
        $this->assertTrue(in_array($raw->activo, [1, '1', true], true));
        $this->assertNotSame('NO', $raw->comodato);
        $this->assertNotSame('SI', $raw->activo);

        $request->update([
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
        ]);

        $provider = PurchaseProvider::factory()->create([
            'nombre' => 'PROVEEDOR CLIENTE',
            'ciudad' => 'BOGOTA',
            'forma_pago' => null,
            'dias_pago' => 15,
        ]);

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->id,
        ]), [
            'provider_id' => $provider->id,
            'fecha' => '2026-10-20',
            'ciudad' => 'CALI',
            'observaciones' => 'Entregar en portería',
            'items' => [[
                'cantidad' => 2,
                'articulo' => 'REF-001',
                'descripcion' => 'CAMARA DE SEGURIDAD',
                'valor_unidad' => 100,
                'iva' => 19,
            ]],
        ])->assertRedirect();

        $order = PurchaseOrder::query()->latest('id')->firstOrFail();

        $this->assertSame('SI', $order->proyecto_nuevo);
        $this->assertSame('NO', $order->asume_cliente);
        $this->assertSame('SI', $order->reinversion);
        $this->assertSame('NO', $order->comodato);
        $this->assertSame('SI', $order->activo);
        $this->assertSame('CALI', $order->ciudad);
        $this->assertSame('BOGOTA', $order->provider_ciudad);

        $html = app(PurchaseOrderPdfExporter::class)->html($order);

        $this->assertStringContainsString('ORDEN DE COMPRA O SERVICIO', $html);
        $this->assertStringContainsString('Enero de 2025', $html);
        $this->assertStringContainsString('FO-AD-07', $html);
        $this->assertStringContainsString('Versión 04', $html);
        $this->assertStringContainsString('Página 1 de 1', $html);
        $this->assertStringContainsString('20/10/2026', $html);
        $this->assertStringContainsString('A 15 DÍAS', $html);
        $this->assertStringContainsString('class="flag-comodato">No</td>', $html);
        $this->assertStringContainsString('class="flag-activo">Sí</td>', $html);
        $this->assertStringContainsString('class="flag-proyecto">Sí</td>', $html);
        $this->assertStringContainsString('OBSERVACIONES', $html);
        $this->assertStringContainsString('ENTREGAR EN PORTERÍA', $html);
        $this->assertStringContainsString('ANALISTA COMPRAS', $html);
        $this->assertStringContainsString('DIRECTOR AREA', $html);
        $this->assertStringContainsString('Director Admon. y Financiero', $html);
        $this->assertStringContainsString('Gerente (cuando aplique)', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertLegalParagraphsInHtml($html);
        $this->assertStringNotContainsString('satisfactoria', $html);

        $beforeAssociate = strstr($html, 'Información del Asociado de Negocio', true);
        $this->assertIsString($beforeAssociate);
        $this->assertStringContainsString('CALI', $beforeAssociate);
        $this->assertStringNotContainsString('BOGOTA', $beforeAssociate);

        $this->assertSame(1, app(PurchaseOrderPdfExporter::class)->pageCount($order));

        $sheet = app(PurchaseOrderExcelExporter::class)->buildSpreadsheet($order)->getActiveSheet();
        $this->assertSame($this->legalText(), $this->normalizeNewlines((string) $this->cellStartingWith($sheet, 'CONDICIONES GENERALES:')));
        $this->assertSame('Sí', $sheet->getCell('B20')->getValue());
        $this->assertSame('No', $sheet->getCell('B19')->getValue());
        $this->assertSame('A 15 DÍAS', $sheet->getCell('B15')->getValue());
        $this->assertSame('CALI', $sheet->getCell('B6')->getValue());
    }

    public function test_interna_leaves_flags_and_snapshots_null_even_if_radios_are_posted(): void
    {
        Mail::fake();

        $director = $this->director('DIRECTOR INTERNO');
        $requester = $this->requester();

        $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'proyecto_nuevo' => '1',
            'asume_cliente' => '1',
            'reinversion' => '1',
            'comodato' => '1',
            'activo' => '1',
            'items' => [[
                'cantidad' => 1,
                'descripcion' => 'resma',
                'referencia' => 'REF-INT',
                'utilizacion' => 'oficina',
                'ubicacion' => 'Cali',
            ]],
        ])->assertRedirect();

        $request = PurchaseRequest::query()->latest('id')->firstOrFail();

        $this->assertNull($request->proyecto_nuevo);
        $this->assertNull($request->asume_cliente);
        $this->assertNull($request->reinversion);
        $this->assertNull($request->comodato);
        $this->assertNull($request->activo);
        $this->assertNull(DB::table('purchase_requests')->where('id', $request->id)->value('comodato'));

        $request->update([
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
        ]);

        $provider = PurchaseProvider::factory()->create();

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.purchase.store', [
            'module' => 'compras',
            'purchase_request' => $request->id,
        ]), [
            'provider_id' => $provider->id,
            'fecha' => now()->toDateString(),
            'ciudad' => 'CALI',
            'items' => [[
                'cantidad' => 1,
                'articulo' => 'REF-INT',
                'descripcion' => 'RESMA',
                'valor_unidad' => 10,
                'iva' => 0,
            ]],
        ])->assertRedirect();

        $order = PurchaseOrder::query()->latest('id')->firstOrFail();

        $this->assertNull($order->proyecto_nuevo);
        $this->assertNull($order->asume_cliente);
        $this->assertNull($order->reinversion);
        $this->assertNull($order->comodato);
        $this->assertNull($order->activo);

        $html = app(PurchaseOrderPdfExporter::class)->html($order);

        $this->assertStringContainsString('class="flag-proyecto">No</td>', $html);
        $this->assertStringContainsString('class="flag-reinversion">No</td>', $html);
        $this->assertStringContainsString('class="flag-comodato">No</td>', $html);
        $this->assertStringContainsString('class="flag-activo">No</td>', $html);
        $this->assertLegalParagraphsInHtml($html);
    }

    public function test_supply_order_keeps_the_five_snapshots_null(): void
    {
        $supplyRequest = $this->approvedSupplyRequest();
        $provider = PurchaseProvider::factory()->create(['nombre' => 'PROVEEDOR SUMINISTROS']);

        $this->actingAs($this->compras)->post(route('purchase-requests.orders.supply.store', [
            'module' => 'compras',
            'supply_request' => $supplyRequest->id,
        ]), [
            'provider_id' => $provider->id,
            'fecha' => now()->toDateString(),
            'ciudad' => 'PALMIRA',
            'items' => [[
                'cantidad' => 4,
                'articulo' => 'REF-SUM',
                'descripcion' => 'PAPELERIA',
                'valor_unidad' => 25,
                'iva' => 0,
            ]],
        ])->assertRedirect();

        $order = PurchaseOrder::query()->latest('id')->firstOrFail();

        $this->assertSame('supply_request', $order->source_type);
        $this->assertNull($order->proyecto_nuevo);
        $this->assertNull($order->asume_cliente);
        $this->assertNull($order->reinversion);
        $this->assertNull($order->comodato);
        $this->assertNull($order->activo);

        $html = app(PurchaseOrderPdfExporter::class)->html($order);

        $this->assertStringContainsString('class="flag-comodato">No</td>', $html);
        $this->assertStringContainsString('REVISOR CALIDAD', $html);
        $this->assertStringContainsString('PALMIRA', $html);
    }

    public function test_an_order_without_flag_snapshots_prints_empty_cells(): void
    {
        $order = PurchaseOrder::factory()->create([
            'ciudad' => 'BUGA',
            'provider_ciudad' => 'MEDELLIN',
            'provider_forma_pago' => 'CREDITO',
            'asume_cliente' => null,
            'proyecto_nuevo' => null,
            'reinversion' => null,
            'comodato' => null,
            'activo' => null,
            'observaciones' => null,
            'source_id' => 999999,
            'created_by' => $this->compras->id,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'cantidad' => 1,
            'articulo' => 'REF-VIEJA',
            'descripcion' => 'LINEA HISTORICA',
            'valor_unidad' => 50,
            'iva' => 0,
            'valor_total' => 50,
        ]);

        $order->update(['total' => 50]);

        $html = app(PurchaseOrderPdfExporter::class)->html($order->fresh());

        $this->assertStringContainsString('class="flag-proyecto">No</td>', $html);
        $this->assertStringContainsString('class="flag-reinversion">No</td>', $html);
        $this->assertStringContainsString('class="flag-comodato">No</td>', $html);
        $this->assertStringContainsString('class="flag-activo">No</td>', $html);
        $this->assertStringContainsString('>Asume el cliente</td>', $html);
        $this->assertStringContainsString('OBSERVACIONES', $html);
        $this->assertStringContainsString('LINEA HISTORICA', $html);
        $this->assertLegalParagraphsInHtml($html);
        $this->assertStringNotContainsString('class="flag-activo">Sí</td>', $html);
    }

    public function test_pdf_stays_on_one_letter_page_with_one_and_twelve_lines(): void
    {
        $one = $this->orderWithLines(1);
        $twelve = $this->orderWithLines(12);
        $exporter = app(PurchaseOrderPdfExporter::class);

        $this->assertSame(1, $exporter->pageCount($one));
        $this->assertSame(1, $exporter->pageCount($twelve));
        $this->assertStringStartsWith('%PDF', $exporter->generate($one));
    }

    public function test_resubmit_persists_cliente_flags_and_clears_them_for_interna(): void
    {
        Mail::fake();

        $director = $this->director('DIRECTOR REENVIO');
        $requester = $this->requester();

        $rejected = PurchaseRequest::query()->create([
            'numero_solicitud' => 8801,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_RECHAZADO,
            'comentarios_director' => 'Ajustar',
        ]);

        $this->actingAs($requester)->patch(route('purchase-requests.update', [
            'module' => 'operaciones',
            'purchase_request' => $rejected->id,
        ]), $this->payload($director, [
            'solicitud_para' => 'Cliente',
            'razon_social' => 'cliente reenvio',
            'proyecto_nuevo' => '0',
            'asume_cliente' => '1',
            'reinversion' => '0',
            'comodato' => '1',
            'activo' => '0',
        ]))->assertRedirect();

        $fresh = $rejected->fresh();
        $this->assertTrue($fresh->comodato);
        $this->assertFalse($fresh->activo);
        $this->assertTrue($fresh->asume_cliente);
        $this->assertIsBool($fresh->comodato);

        $fresh->update([
            'estado' => PurchaseRequest::ESTADO_RECHAZADO,
            'comentarios_director' => 'Volver a interna',
        ]);

        $this->actingAs($requester)->patch(route('purchase-requests.update', [
            'module' => 'operaciones',
            'purchase_request' => $fresh->id,
        ]), $this->payload($director, [
            'solicitud_para' => 'Interno',
            'comodato' => '1',
            'activo' => '1',
            'proyecto_nuevo' => '1',
            'asume_cliente' => '1',
            'reinversion' => '1',
        ]))->assertRedirect();

        $interna = $fresh->fresh();
        $this->assertSame('Interno', $interna->solicitud_para);
        $this->assertNull($interna->comodato);
        $this->assertNull($interna->activo);
        $this->assertNull($interna->proyecto_nuevo);
        $this->assertNull($interna->asume_cliente);
        $this->assertNull($interna->reinversion);
    }

    public function test_create_edit_and_show_render_the_client_flag_row(): void
    {
        $director = $this->director('DIRECTOR VISTA');
        $requester = $this->requester();

        $create = $this->actingAs($requester)->get(route('purchase-requests.create', ['module' => 'operaciones']));
        $create->assertOk();
        $create->assertSee('Comodato');
        $create->assertSee('Activo');
        $this->assertMatchesRegularExpression(
            '/id="purchase-cliente-fields"[^>]*\shidden/',
            $create->getContent(),
        );

        $cliente = PurchaseRequest::query()->create([
            'numero_solicitud' => 8802,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Cliente',
            'razon_social' => 'CLIENTE VISTA',
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_RECHAZADO,
            'proyecto_nuevo' => true,
            'asume_cliente' => false,
            'reinversion' => false,
            'comodato' => true,
            'activo' => false,
            'comentarios_director' => 'Revisar',
        ]);

        $this->actingAs($requester)
            ->get(route('purchase-requests.show', [
                'module' => 'operaciones',
                'purchase_request' => $cliente->id,
            ]))
            ->assertOk()
            ->assertSee('Comodato')
            ->assertSee('Activo')
            ->assertSee('Sí')
            ->assertSee('No');

        $this->actingAs($requester)
            ->get(route('purchase-requests.edit', [
                'module' => 'operaciones',
                'purchase_request' => $cliente->id,
            ]))
            ->assertOk()
            ->assertSee('Comodato')
            ->assertSee('Activo')
            ->assertSee('name="comodato"', false);

        $interna = PurchaseRequest::query()->create([
            'numero_solicitud' => 8803,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($requester)
            ->get(route('purchase-requests.show', [
                'module' => 'operaciones',
                'purchase_request' => $interna->id,
            ]))
            ->assertOk()
            ->assertDontSee('Comodato')
            ->assertDontSee('Activo');
    }

    private function assertLegalParagraphsInHtml(string $html): void
    {
        foreach (preg_split('/\R{2,}/', $this->legalText()) as $parrafo) {
            $parrafo = trim($parrafo);

            if ($parrafo !== '') {
                $this->assertStringContainsString($parrafo, $html);
            }
        }
    }

    private function legalText(): string
    {
        $analyst = (string) file_get_contents(base_path('docs/briefs/FEAT-048-analyst.md'));

        $this->assertSame(1, preg_match(
            '/```\r?\n(CONDICIONES GENERALES:[\s\S]*?RELACIÓN COMERCIAL)\r?\n```/',
            $analyst,
            $matches,
        ));

        return $this->normalizeNewlines($matches[1]);
    }

    private function normalizeNewlines(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }

    private function cellStartingWith(Worksheet $sheet, string $prefix): ?string
    {
        foreach ($sheet->getRowIterator() as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();

                if (is_string($value) && str_starts_with($value, $prefix)) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function orderWithLines(int $lines): PurchaseOrder
    {
        $order = PurchaseOrder::factory()->create([
            'ciudad' => 'CALI',
            'provider_ciudad' => 'BOGOTA',
            'provider_forma_pago' => null,
            'provider_dias_pago' => 30,
            'asume_cliente' => 'SI',
            'proyecto_nuevo' => 'NO',
            'reinversion' => 'SI',
            'comodato' => 'NO',
            'activo' => 'SI',
            'observaciones' => 'OBSERVACION DE PRUEBA PARA LA HOJA',
            'source_id' => 999999,
            'created_by' => $this->compras->id,
            'total' => $lines * 100,
        ]);

        for ($index = 1; $index <= $lines; $index++) {
            PurchaseOrderItem::factory()->create([
                'purchase_order_id' => $order->id,
                'orden' => $index,
                'cantidad' => 1,
                'articulo' => 'REF-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'descripcion' => 'SUMINISTRO DE REPUESTO PARA EL SERVICIO CONTRATADO LINEA '.$index,
                'valor_unidad' => 100,
                'iva' => 19,
                'valor_total' => 119,
            ]);
        }

        return $order->fresh(['items']);
    }

    private function director(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@test.local',
        ]);
        $user->assignRole('director');

        return $user;
    }

    private function requester(): User
    {
        $user = User::factory()->create(['area_key' => 'operaciones']);
        $user->givePermissionTo([
            'purchase.tab.create',
            'purchase.tab.my_requests',
            'view.board.operaciones.solicitudes_compra',
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(User $director, array $overrides = []): array
    {
        return array_merge([
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'items' => [[
                'cantidad' => 1,
                'descripcion' => 'Monitor',
                'referencia' => 'Dell',
                'utilizacion' => 'Oficina',
                'ubicacion' => 'Cali',
            ]],
        ], $overrides);
    }

    private function approvedSupplyRequest(): SupplyRequest
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $requester = User::factory()->create(['area_key' => 'operaciones', 'sede_id' => $site->id]);
        $reviewer = User::factory()->create(['name' => 'REVISOR CALIDAD']);

        $request = SupplyRequest::query()->create([
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'en_compras',
            'quality_reviewer_id' => $reviewer->id,
        ]);

        $request->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => 10,
            'approved_quantity' => 4,
            'unit_cost' => 25,
            'is_not_in_catalog' => false,
        ]);

        return $request->load('items.product');
    }
}
