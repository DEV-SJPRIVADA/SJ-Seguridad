<?php

namespace Tests\Feature;

use App\Mail\PurchaseRequestCreatedMail;
use App\Models\PurchaseRequest;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PurchaseRequestModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_create_purchase_request_and_notify_selected_director(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $response = $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'items' => [
                [
                    'cantidad' => 2,
                    'descripcion' => 'Monitor 24 pulgadas',
                    'referencia' => 'Dell P2422H',
                    'utilizacion' => 'Puesto de trabajo',
                    'ubicacion' => 'Cali',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase-requests.create', ['module' => 'operaciones']));
        $this->assertDatabaseHas('purchase_requests', [
            'user_id' => $requester->id,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);

        Mail::assertQueued(PurchaseRequestCreatedMail::class, fn ($mail) => $mail->hasTo($director->email));
        Mail::assertNotQueued(PurchaseRequestCreatedMail::class, fn ($mail) => $mail->hasTo($requester->email));
    }

    public function test_user_can_upload_item_photo_when_creating_purchase_request(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $response = $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Teclado mecanico',
                    'referencia' => 'KB-001',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Bogota',
                    'foto' => UploadedFile::fake()->image('teclado.jpg'),
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase-requests.create', ['module' => 'operaciones']));

        $item = PurchaseRequest::query()->first()?->items()->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->foto_path);
        $this->assertTrue(Storage::disk('public')->exists($item->foto_path));
    }

    public function test_director_sees_only_assigned_pending_requests(): void
    {
        $directorA = $this->director('director.a@test.local');
        $directorB = $this->director('director.b@test.local');
        $requester = $this->purchaseRequester('operaciones');

        $assigned = $this->createPurchaseRequest($requester, $directorA);
        $this->createPurchaseRequest($requester, $directorB);

        $response = $this->actingAs($directorA)->get(route('purchase-requests.approval.index', ['module' => 'compras']));

        $response->assertOk();
        $response->assertSee($assigned->folio());
        $response->assertDontSee(PurchaseRequest::query()->where('aprobador_id', $directorB->id)->first()->folio());
    }

    public function test_non_assigned_director_cannot_approve(): void
    {
        $directorA = $this->director('director.a@test.local');
        $directorB = $this->director('director.b@test.local');
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $directorA);

        $this->actingAs($directorB)
            ->patch(route('purchase-requests.approval.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
                'estado' => PurchaseRequest::ESTADO_APROBADO,
            ])
            ->assertForbidden();
    }

    public function test_approved_purchase_appears_in_compras_queue(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $compras = $this->comprasUser();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
        ])->assertRedirect();

        $response = $this->actingAs($compras)->get(route('purchase-requests.processing.index', ['module' => 'compras']));

        $response->assertOk();
        $response->assertSee($purchaseRequest->fresh()->folio());
    }

    public function test_email_approval_signed_route_works(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $url = URL::temporarySignedRoute(
            'purchase-requests.email-approval.update',
            now()->addHour(),
            ['purchase_request' => $purchaseRequest->id, 'director' => $director->id],
        );

        $this->post($url, [
            'director' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
        ])->assertRedirect();

        $this->assertSame(PurchaseRequest::ESTADO_APROBADO, $purchaseRequest->fresh()->estado);
    }

    public function test_supply_approval_appears_in_compras_queue(): void
    {
        Mail::fake();

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

        $this->actingAs($qualityUser)->patch(route('supplies.approval.update', ['module' => 'operaciones', 'supply_request' => $supplyRequest->id]), [
            'action' => 'approve',
            'items' => [$item->id => ['approved_quantity' => 4]],
        ])->assertRedirect();

        $response = $this->actingAs($compras)->get(route('purchase-requests.processing.index', ['module' => 'compras']));

        $response->assertOk();
        $response->assertSee('#'.$supplyRequest->id);
    }

    private function purchaseRequester(string $area): User
    {
        $user = User::factory()->create([
            'area_key' => $area,
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(['purchase.tab.create', 'purchase.tab.my_requests', "view.board.{$area}.solicitudes_compra"]);

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

    private function createPurchaseRequest(User $requester, User $director): PurchaseRequest
    {
        return PurchaseRequest::query()->create([
            'numero_solicitud' => (PurchaseRequest::query()->max('numero_solicitud') ?? 0) + 1,
            'user_id' => $requester->id,
            'area_key' => $requester->area_key,
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'Prueba',
            'cantidad' => 1,
            'justificacion' => 'Prueba',
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);
    }
}
