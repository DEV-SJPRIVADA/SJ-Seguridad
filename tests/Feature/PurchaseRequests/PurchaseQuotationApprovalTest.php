<?php

namespace Tests\Feature\PurchaseRequests;

use App\Mail\PurchaseOrderApprovalRequestedMail;
use App\Mail\PurchaseQuotationApprovalRequestedMail;
use App\Mail\PurchaseQuotationApprovalResolvedMail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequests\PurchaseOrderApprovalService;
use App\Services\PurchaseRequests\PurchaseOrderPdfExporter;
use App\Services\PurchaseRequests\PurchaseOrderService;
use App\Services\PurchaseRequests\PurchaseQuotationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Cotizaciones de la bandeja de Compras agrupadas por lote (OC futura): carga de 1..3
 * archivos, aprobación de un director que elige una ganadora por lote (plataforma o
 * correo) y vínculo opcional de esa ganadora con la OC. Un lote pendiente bloquea la OC.
 */
class PurchaseQuotationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    private User $director;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create(['name' => 'Analista Compras', 'area_key' => 'compras', 'must_change_password' => false]);
        $this->compras->givePermissionTo('purchase.tab.processing');

        $this->director = User::factory()->create(['name' => 'Directora Cotizaciones', 'email' => 'directora.cotizaciones@test.local', 'must_change_password' => false]);
        $this->director->assignRole('director');

        $this->admin = User::factory()->create(['name' => 'Administrador Cotizaciones', 'email' => 'admin.cotizaciones@test.local', 'must_change_password' => false]);
        $this->admin->assignRole('administrador');
    }

    public function test_compras_uploads_a_batch_and_pending_batch_blocks_the_order(): void
    {
        Mail::fake();
        $purchase = $this->purchase();

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.quotations.store', ['module' => 'compras', 'purchase_request' => $purchase->id]), [
                'lote_label' => 'oc 1 · asociado a',
                'files' => [
                    1 => UploadedFile::fake()->create('cotizacion-a.pdf', 120, 'application/pdf'),
                    2 => UploadedFile::fake()->create('cotizacion-b.pdf', 120, 'application/pdf'),
                    3 => UploadedFile::fake()->create('cotizacion-c.jpg', 120, 'image/jpeg'),
                ],
                'descriptions' => [
                    1 => 'cotizacion proveedor a',
                    2 => 'cotizacion proveedor b',
                    3 => 'cotizacion proveedor c',
                ],
            ])
            ->assertRedirect();

        $quotations = PurchaseQuotation::query()->orderBy('lote_orden')->get();
        $this->assertCount(3, $quotations);
        $this->assertSame(1, $quotations->pluck('lote')->unique()->count());
        $this->assertSame('OC 1 · ASOCIADO A', $quotations->first()->lote_label);
        $this->assertSame('COTIZACION PROVEEDOR A', $quotations->first()->description);
        $this->assertTrue($quotations->every(fn (PurchaseQuotation $quotation): bool => $quotation->estado === PurchaseQuotation::ESTADO_CARGADA));
        Storage::disk('local')->assertExists($quotations->first()->stored_path);

        $lote = (string) $quotations->first()->lote;

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.quotations.approval.request', [
                'module' => 'compras',
                'purchase_request' => $purchase->id,
                'lote' => $lote,
            ]), ['aprobador_id' => $this->director->id])
            ->assertRedirect();

        $quotations->each(function (PurchaseQuotation $quotation) use ($lote): void {
            $quotation->refresh();
            $this->assertSame(PurchaseQuotation::ESTADO_PENDIENTE, $quotation->estado);
            $this->assertSame($this->director->id, $quotation->aprobador_id);
            $this->assertSame($lote, $quotation->lote);
        });

        $this->assertTrue($purchase->fresh()->tieneCotizacionPendienteAprobacion());
        Mail::assertSent(PurchaseQuotationApprovalRequestedMail::class, fn ($mail) => $mail->hasTo($this->director->email));

        // Procesar muestra el lote pendiente y oculta el acceso a emitir la OC.
        $this->actingAs($this->compras)
            ->get(route('purchase-requests.processing.purchase', ['module' => 'compras', 'purchase_request' => $purchase->id]))
            ->assertOk()
            ->assertSee('Cotizaciones')
            ->assertSee('Hay grupos de cotizaciones pendientes de aprobación.')
            ->assertDontSee('orden-compra/crear');

        // La pantalla de emisión no abre mientras el lote esté pendiente.
        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.purchase.create', ['module' => 'compras', 'purchase_request' => $purchase->id]))
            ->assertRedirect(route('purchase-requests.processing.purchase', ['module' => 'compras', 'purchase_request' => $purchase->id]))
            ->assertSessionHas('error');

        // El servicio también bloquea la emisión aunque se llame directo.
        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.purchase.store', ['module' => 'compras', 'purchase_request' => $purchase->id]), $this->orderPayload())
            ->assertSessionHasErrors('items');

        $this->assertSame(0, PurchaseOrder::query()->count());
    }

    public function test_director_approves_one_quotation_per_batch_and_the_order_links_it(): void
    {
        Mail::fake();
        $purchase = $this->purchase();
        [$lote, $quotations] = $this->batch($purchase);
        $selected = $quotations[0];
        $notSelected = $quotations[1];

        app(PurchaseQuotationService::class)->request($purchase, $lote, $this->compras, $this->director->id);

        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'cotizaciones']))
            ->assertOk()
            ->assertSee('Cotizaciones')
            ->assertSee($purchase->folioVisible())
            ->assertSee('OC 1 · ASOCIADO A')
            ->assertSee('Revisar grupo');

        $this->actingAs($this->admin)
            ->get(route('purchase-requests.approval.quotation.show', ['module' => 'compras', 'lote' => $lote]))
            ->assertForbidden();

        // El aprobador elegido ve el grupo con sus documentos y el panel de decisión.
        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.quotation.show', ['module' => 'compras', 'lote' => $lote]))
            ->assertOk()
            ->assertSee('Aprobación de cotizaciones')
            ->assertSee('OC 1 · ASOCIADO A')
            ->assertSee('Cotizaciones del grupo')
            ->assertSee('Cotización ganadora')
            ->assertSee('Aprobar grupo');

        $this->actingAs($this->director)
            ->patch(route('purchase-requests.approval.quotation.update', ['module' => 'compras', 'lote' => $lote]), [
                'decision' => 'aprobar',
                'purchase_quotation_id' => $selected->id,
                'comentario' => 'cumple con lo solicitado',
            ])
            ->assertRedirect(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'cotizaciones']));

        $selected->refresh();
        $notSelected->refresh();
        $this->assertSame(PurchaseQuotation::ESTADO_APROBADA, $selected->estado);
        $this->assertTrue($selected->seleccionada);
        $this->assertSame(PurchaseQuotation::ESTADO_NO_SELECCIONADA, $notSelected->estado);
        $this->assertFalse($notSelected->seleccionada);
        $this->assertSame('CUMPLE CON LO SOLICITADO', $selected->comentario);
        Mail::assertSent(PurchaseQuotationApprovalResolvedMail::class, fn ($mail) => $mail->hasTo($this->compras->email) && $mail->approved);

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.purchase.create', ['module' => 'compras', 'purchase_request' => $purchase->id]))
            ->assertOk()
            ->assertSee('Cotización aprobada (opcional)')
            ->assertSee('OC 1 · ASOCIADO A')
            ->assertSee($selected->descripcionVisible());

        // Solo la ganadora puede vincularse.
        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.purchase.store', ['module' => 'compras', 'purchase_request' => $purchase->id]), $this->orderPayload([
                'purchase_quotation_id' => $notSelected->id,
            ]))
            ->assertSessionHasErrors('purchase_quotation_id');

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.purchase.store', ['module' => 'compras', 'purchase_request' => $purchase->id]), $this->orderPayload([
                'purchase_quotation_id' => $selected->id,
            ]))
            ->assertRedirect();

        $order = PurchaseOrder::query()->firstOrFail();
        $this->assertSame($selected->id, $order->purchase_quotation_id);

        $this->actingAs($this->compras)
            ->get(route('purchase-requests.orders.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertSee('Cotización aprobada vinculada')
            ->assertSee('OC 1 · ASOCIADO A')
            ->assertSee($selected->descripcionVisible())
            ->assertSee('Directora Cotizaciones');

        $html = app(PurchaseOrderPdfExporter::class)->html($order->fresh());
        $this->assertStringContainsString($selected->descripcionVisible(), $html);
        $this->assertStringContainsString('Directora Cotizaciones', $html);

        // La cotización ya consumida no puede vincularse a otra OC.
        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.purchase.store', ['module' => 'compras', 'purchase_request' => $purchase->id]), $this->orderPayload([
                'purchase_quotation_id' => $selected->id,
            ]))
            ->assertSessionHasErrors('purchase_quotation_id');
    }

    public function test_the_order_approval_screens_show_the_linked_quotation(): void
    {
        Mail::fake();
        $purchase = $this->purchase();
        [$lote, $quotations] = $this->batch($purchase);
        $selected = $quotations[0];

        // El director aprueba la cotización ganadora del grupo.
        app(PurchaseQuotationService::class)->request($purchase, $lote, $this->compras, $this->director->id);
        app(PurchaseQuotationService::class)->resolve($purchase, $lote, $this->director->id, true, $selected->id, null, 'web');

        // Compras emite la OC vinculando esa cotización.
        app(PurchaseOrderService::class)->createFromPurchase($purchase->fresh('items'), array_merge($this->orderPayload(), [
            'purchase_quotation_id' => $selected->id,
        ]), $this->compras);

        $order = PurchaseOrder::query()->firstOrFail();
        $this->assertSame($selected->id, $order->purchase_quotation_id);

        // Compras envía la OC a autorizar al director.
        app(PurchaseOrderApprovalService::class)->request($order, $this->compras, $this->director->id, null);

        // El listado de Autorizaciones compras → Órdenes de Compra muestra la cotización aprobada.
        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'vista' => 'ordenes']))
            ->assertOk()
            ->assertSee('Cotización aprobada')
            ->assertSee($selected->descripcionVisible())
            ->assertSee('OC 1 · ASOCIADO A')
            ->assertSee('Directora Cotizaciones');

        // La pantalla de revisión de la orden también la muestra.
        $this->actingAs($this->director)
            ->get(route('purchase-requests.approval.order-approval.show', ['module' => 'compras', 'purchase_order' => $order->id]))
            ->assertOk()
            ->assertSee('Cotización aprobada')
            ->assertSee($selected->descripcionVisible())
            ->assertSee('OC 1 · ASOCIADO A')
            ->assertSee('Aprobar orden');

        // El enlace firmado del correo la incluye en el resumen.
        $this->get(app(PurchaseOrderApprovalService::class)->emailShowUrl($order->fresh()))
            ->assertOk()
            ->assertSee('Cotización aprobada')
            ->assertSee($selected->descripcionVisible());

        // Y el correo al aprobador también la incluye.
        $mail = (new PurchaseOrderApprovalRequestedMail($order->fresh('purchaseQuotation.aprobador'), 'http://localhost/aprobar'))->render();
        $this->assertStringContainsString('Cotización aprobada', $mail);
        $this->assertStringContainsString($selected->descripcionVisible(), $mail);
    }

    public function test_rejection_requires_a_comment_and_the_order_can_still_be_emitted_without_quotation(): void
    {
        Mail::fake();
        $purchase = $this->purchase();
        [$lote] = $this->batch($purchase, 1);

        app(PurchaseQuotationService::class)->request($purchase, $lote, $this->compras, $this->director->id);

        $this->actingAs($this->director)
            ->patch(route('purchase-requests.approval.quotation.update', ['module' => 'compras', 'lote' => $lote]), [
                'decision' => 'rechazar',
            ])
            ->assertSessionHasErrors('comentario');

        $this->actingAs($this->director)
            ->patch(route('purchase-requests.approval.quotation.update', ['module' => 'compras', 'lote' => $lote]), [
                'decision' => 'rechazar',
                'comentario' => 'precio fuera de mercado',
            ])
            ->assertRedirect();

        $quotation = PurchaseQuotation::query()->firstOrFail();
        $this->assertSame(PurchaseQuotation::ESTADO_RECHAZADA, $quotation->estado);
        $this->assertFalse($quotation->seleccionada);
        $this->assertSame('PRECIO FUERA DE MERCADO', $quotation->comentario);
        Mail::assertSent(PurchaseQuotationApprovalResolvedMail::class, fn ($mail) => $mail->hasTo($this->compras->email) && ! $mail->approved);

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.purchase.store', ['module' => 'compras', 'purchase_request' => $purchase->id]), $this->orderPayload())
            ->assertRedirect();

        $order = PurchaseOrder::query()->firstOrFail();
        $this->assertNull($order->purchase_quotation_id);
    }

    public function test_approver_decides_from_the_signed_email_link_without_login(): void
    {
        Mail::fake();
        $purchase = $this->purchase();
        [$lote, $quotations] = $this->batch($purchase);
        $service = app(PurchaseQuotationService::class);
        $service->request($purchase, $lote, $this->compras, $this->director->id);

        $this->get($service->emailShowUrl($purchase, $lote, $this->director->id))
            ->assertOk()
            ->assertSee('Aprobar grupo');

        $this->post($service->emailUpdateUrl($purchase, $lote, $this->director->id), [
            'decision' => 'aprobar',
            'purchase_quotation_id' => $quotations[2]->id,
        ])
            ->assertOk()
            ->assertSee('aprobada');

        $this->assertSame(PurchaseQuotation::ESTADO_APROBADA, $quotations[2]->fresh()->estado);
        $this->assertTrue($quotations[2]->fresh()->seleccionada);
        $this->assertSame(0, PurchaseQuotation::query()->where('estado', PurchaseQuotation::ESTADO_PENDIENTE)->count());
    }

    public function test_two_batches_can_be_pending_and_each_order_links_its_selected_quotation(): void
    {
        Mail::fake();
        $purchase = $this->purchase();
        [$loteA, $quotationsA] = $this->batch($purchase, 3, 'oc 1 · asociado a');
        [$loteB, $quotationsB] = $this->batch($purchase, 3, 'oc 2 · asociado b');

        $service = app(PurchaseQuotationService::class);
        $service->request($purchase, $loteA, $this->compras, $this->director->id);
        $service->request($purchase, $loteB, $this->compras, $this->director->id);

        // Dos autorizaciones pendientes a la vez (una por OC futura).
        $this->assertSame(2, PurchaseQuotation::query()
            ->where('estado', PurchaseQuotation::ESTADO_PENDIENTE)
            ->distinct()
            ->count('lote'));

        foreach ([[$loteA, $quotationsA[0]], [$loteB, $quotationsB[1]]] as [$lote, $selected]) {
            $this->actingAs($this->director)
                ->patch(route('purchase-requests.approval.quotation.update', ['module' => 'compras', 'lote' => $lote]), [
                    'decision' => 'aprobar',
                    'purchase_quotation_id' => $selected->id,
                ])
                ->assertRedirect();
        }

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.purchase.store', ['module' => 'compras', 'purchase_request' => $purchase->id]), $this->orderPayload([
                'purchase_quotation_id' => $quotationsA[0]->id,
            ]))
            ->assertRedirect();

        $this->actingAs($this->compras)
            ->post(route('purchase-requests.orders.purchase.store', ['module' => 'compras', 'purchase_request' => $purchase->id]), $this->orderPayload([
                'purchase_quotation_id' => $quotationsB[1]->id,
            ]))
            ->assertRedirect();

        $orders = PurchaseOrder::query()->orderBy('id')->get();
        $this->assertCount(2, $orders);
        $this->assertSame($quotationsA[0]->id, $orders[0]->purchase_quotation_id);
        $this->assertSame($quotationsB[1]->id, $orders[1]->purchase_quotation_id);
    }

    private function purchase(int $numero = 8801): PurchaseRequest
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

        return $purchase->fresh('items');
    }

    /**
     * @return array{0: string, 1: Collection<int, PurchaseQuotation>}
     */
    private function batch(PurchaseRequest $purchase, int $count = 3, string $label = 'oc 1 · asociado a'): array
    {
        $files = [];
        $descriptions = [];

        for ($i = 1; $i <= $count; $i++) {
            $files[$i] = UploadedFile::fake()->create('cotizacion-'.$i.'.pdf', 120, 'application/pdf');
            $descriptions[$i] = 'cotizacion proveedor '.$i;
        }

        $lote = app(PurchaseQuotationService::class)->storeBatch(
            $purchase,
            $this->compras,
            null,
            $label,
            $files,
            $descriptions,
        );

        return [
            $lote,
            PurchaseQuotation::query()->where('lote', $lote)->orderBy('lote_orden')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function orderPayload(array $extra = []): array
    {
        return array_merge([
            'provider_id' => PurchaseProvider::factory()->create()->id,
            'fecha' => now()->toDateString(),
            'observaciones' => 'PRUEBA',
            'items' => [
                ['cantidad' => 1, 'descripcion' => 'CAMARA', 'valor_unidad' => 100, 'iva_porcentaje' => 19],
            ],
        ], $extra);
    }
}
