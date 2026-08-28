<?php

namespace Tests\Feature\PurchaseRequests;

use App\Models\AuditLog;
use App\Models\PurchaseRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PurchaseRequestAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
        Mail::fake();
    }

    public function test_store_writes_create_audit_event(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'items' => [
                [
                    'cantidad' => 2,
                    'descripcion' => 'Monitor',
                    'referencia' => 'Dell',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Cali',
                ],
            ],
        ])->assertRedirect();

        $purchaseRequest = PurchaseRequest::query()->firstOrFail();

        $log = AuditLog::query()
            ->where('module', 'purchase_requests')
            ->where('event_type', 'purchase_request')
            ->where('action', 'create')
            ->firstOrFail();

        $this->assertSame('compras', $log->area);
        $this->assertSame($requester->id, $log->user_id);
        $this->assertSame($purchaseRequest->id, $log->auditable_id);
        $this->assertSame($purchaseRequest->numero_solicitud, $log->metadata['numero_solicitud']);
        $this->assertSame('operaciones', $log->metadata['area_key']);
        $this->assertSame(1, $log->metadata['items_count']);
        $this->assertSame(0, $log->metadata['attachments_count']);
        $this->assertFalse($log->metadata['urgente']);
        $this->assertSame($director->id, $log->metadata['aprobador_id']);
    }

    public function test_resubmit_writes_resubmit_audit_event(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest(
            $requester,
            $director,
            PurchaseRequest::ESTADO_RECHAZADO,
            'Faltan referencias',
        );

        $this->actingAs($requester)->patch(
            route('purchase-requests.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]),
            [
                'area_key' => 'operaciones',
                'fecha_solicitud' => now()->toDateString(),
                'solicitud_para' => 'Interno',
                'urgente' => false,
                'aprobador_id' => $director->id,
                'items' => [[
                    'cantidad' => 1,
                    'descripcion' => 'Teclado',
                    'referencia' => 'KB-001',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Cali',
                ]],
            ]
        )->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'purchase_requests')
            ->where('event_type', 'purchase_request')
            ->where('action', 'resubmit')
            ->firstOrFail();

        $this->assertSame($purchaseRequest->numero_solicitud, $log->metadata['numero_solicitud']);
        $this->assertSame(1, $log->metadata['items_count']);
        $this->assertSame(0, $log->metadata['attachments_count']);
        $this->assertSame(PurchaseRequest::ESTADO_RECHAZADO, $log->metadata['previous_estado']);
    }

    public function test_director_web_approve_writes_audit_event(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', [
            'module' => 'compras',
            'purchase_request' => $purchaseRequest->id,
        ]), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'comentarios_director' => 'Autorizado para compra.',
        ])->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'purchase_requests')
            ->where('event_type', 'director_approval')
            ->where('action', 'approve')
            ->firstOrFail();

        $this->assertSame('compras', $log->area);
        $this->assertSame($director->id, $log->user_id);
        $this->assertSame($purchaseRequest->folio(), $log->metadata['folio']);
        $this->assertSame('web', $log->metadata['channel']);
        $this->assertStringContainsString('Autorizado para compra', $log->metadata['comentarios_director']);
    }

    public function test_email_approval_reject_writes_audit_with_director_user(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->post($this->emailApprovalUpdateUrl($purchaseRequest, $director), [
            'estado' => PurchaseRequest::ESTADO_RECHAZADO,
            'comentarios_director' => 'No cumple politica de compras.',
        ])->assertOk();

        $log = AuditLog::query()
            ->where('module', 'purchase_requests')
            ->where('event_type', 'director_approval')
            ->where('action', 'reject')
            ->firstOrFail();

        $this->assertSame('email', $log->metadata['channel']);
        $this->assertSame($director->id, $log->user_id);
        $this->assertSame('No cumple politica de compras.', $log->metadata['comentarios_director']);
    }

    public function test_update_purchase_status_change_writes_audit_event(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $compras = $this->comprasUser();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', [
            'module' => 'compras',
            'purchase_request' => $purchaseRequest->id,
        ]), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
        ])->assertRedirect();

        $purchaseRequest->refresh();
        $this->assertSame(PurchaseRequest::COMPRAS_PENDIENTE, $purchaseRequest->estado_compras);

        $this->actingAs($compras)->patch(route('purchase-requests.processing.purchase.update', [
            'module' => 'compras',
            'purchase_request' => $purchaseRequest->id,
        ]), [
            'estado_compras' => PurchaseRequest::COMPRAS_EN_CURSO,
            'comentarios_compras' => 'Cotizacion en curso.',
        ])->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'purchase_requests')
            ->where('event_type', 'compras_processing')
            ->where('action', 'status_change')
            ->firstOrFail();

        $this->assertSame(['estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE], $log->old_values);
        $this->assertSame(['estado_compras' => PurchaseRequest::COMPRAS_EN_CURSO], $log->new_values);
        $this->assertSame($purchaseRequest->folio(), $log->metadata['folio']);
        $this->assertSame($compras->id, $log->user_id);
    }

    public function test_update_supply_complete_writes_status_change_audit_event(): void
    {
        $qualityUser = $this->qualityReviewer('operaciones');
        $compras = $this->comprasUser();
        $requester = $this->supplyRequester('operaciones');
        $product = SupplyProduct::query()->firstOrFail();

        $this->actingAs($requester)->post(route('supplies.store', ['module' => 'operaciones']), [
            'items' => [[
                'type' => 'catalog',
                'product_id' => $product->id,
                'current_inventory' => 1,
                'quantity' => 5,
            ]],
        ]);

        $supplyRequest = SupplyRequest::query()->latest()->firstOrFail();
        $item = $supplyRequest->items()->firstOrFail();

        $this->actingAs($qualityUser)->patch(route('supplies.approval.update', [
            'module' => 'operaciones',
            'supply_request' => $supplyRequest->id,
        ]), [
            'action' => 'approve',
            'items' => [$item->id => ['approved_quantity' => 4]],
        ])->assertRedirect();

        $supplyRequest->refresh();
        $this->assertSame('aprobada_calidad', $supplyRequest->status);

        $this->actingAs($compras)->patch(route('purchase-requests.processing.supply.update', [
            'module' => 'compras',
            'supply_request' => $supplyRequest->id,
        ]), [
            'action' => 'complete',
            'items' => [
                $item->id => ['unit_cost' => 1000],
            ],
        ])->assertRedirect();

        $log = AuditLog::query()
            ->where('module', 'purchase_requests')
            ->where('event_type', 'supply_compras')
            ->where('action', 'status_change')
            ->firstOrFail();

        $this->assertSame(['status' => 'aprobada_calidad'], $log->old_values);
        $this->assertSame(['status' => 'completada'], $log->new_values);
        $this->assertSame('complete', $log->metadata['action']);
        $this->assertSame('4000.00', (string) $log->metadata['total_cost']);
    }

    private function purchaseRequester(string $area): User
    {
        $user = User::factory()->create([
            'area_key' => $area,
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'purchase.tab.create',
            'purchase.tab.my_requests',
            "view.board.{$area}.solicitudes_compra",
        ]);

        return $user;
    }

    private function director(string $email = 'director@test.local'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'must_change_password' => false,
        ]);
        $user->assignRole('director');

        return $user;
    }

    private function comprasUser(): User
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(['purchase.tab.processing', 'view.board.compras.bandeja_compras']);

        return $user;
    }

    private function qualityReviewer(string $area): User
    {
        $user = User::factory()->create([
            'area_key' => $area,
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(['supply.tab.quality', "view.board.{$area}.suministros"]);

        return $user;
    }

    private function supplyRequester(string $area): User
    {
        $user = User::factory()->create([
            'area_key' => $area,
            'must_change_password' => false,
            'sede_id' => SupplySite::query()->value('id'),
        ]);
        $user->givePermissionTo(['supply.tab.my_requests', "view.board.{$area}.suministros"]);

        return $user;
    }

    private function createPurchaseRequest(
        User $requester,
        User $director,
        string $estado = PurchaseRequest::ESTADO_PENDIENTE,
        ?string $comentariosDirector = null,
    ): PurchaseRequest {
        return PurchaseRequest::query()->create([
            'numero_solicitud' => (PurchaseRequest::query()->max('numero_solicitud') ?? 0) + 1,
            'user_id' => $requester->id,
            'area_key' => $requester->area_key,
            'fecha_solicitud' => now()->toDateString(),
            'cantidad' => 1,
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => $estado,
            'fecha_aprobacion' => $estado === PurchaseRequest::ESTADO_PENDIENTE ? null : now()->toDateString(),
            'comentarios_director' => $comentariosDirector,
        ]);
    }

    private function emailApprovalUpdateUrl(PurchaseRequest $purchaseRequest, User $director): string
    {
        return URL::temporarySignedRoute(
            'purchase-requests.email-approval.update',
            now()->addHour(),
            ['purchase_request' => $purchaseRequest->id, 'director' => $director->id],
        );
    }
}
