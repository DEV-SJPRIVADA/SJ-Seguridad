<?php

namespace Tests\Feature;

use App\Mail\SupplyRequestNotification;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupplyModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_supply_catalog_is_seeded(): void
    {
        $this->assertGreaterThan(0, SupplyProduct::query()->count());
    }

    public function test_user_can_create_supply_request(): void
    {
        $user = $this->requester('operaciones');
        $product = SupplyProduct::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('supplies.store', ['module' => 'operaciones']), [
            'observations' => 'Pedido mensual de aseo.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'current_inventory' => 2,
                    'quantity' => 10,
                ],
            ],
        ]);

        $response->assertRedirect(route('supplies.index', ['module' => 'operaciones']));
        $this->assertDatabaseHas('supply_requests', [
            'user_id' => $user->id,
            'area_key' => 'operaciones',
            'status' => 'pendiente_calidad',
        ]);
        $this->assertDatabaseHas('supply_request_items', [
            'supply_product_id' => $product->id,
            'requested_quantity' => 10,
            'current_inventory' => 2,
        ]);
    }

    public function test_owner_can_view_its_own_supply_request(): void
    {
        $user = $this->requester('operaciones');
        $request = $this->supplyRequest($user, 'operaciones');

        $response = $this->actingAs($user)->get(route('supplies.show', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]));

        $response->assertOk();
    }

    public function test_non_owner_without_review_access_cannot_view_supply_request(): void
    {
        $owner = $this->requester('operaciones');
        $intruder = $this->requester('operaciones');
        $request = $this->supplyRequest($owner, 'operaciones');

        $response = $this->actingAs($intruder)->get(route('supplies.show', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]));

        $response->assertForbidden();
    }

    public function test_quality_reviewer_can_view_any_supply_request(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->requester('calidad');
        $reviewer->givePermissionTo('supply.tab.quality');

        $request = $this->supplyRequest($owner, 'operaciones');

        $response = $this->actingAs($reviewer)->get(route('supplies.show', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]));

        $response->assertOk();
    }

    public function test_quality_can_approve_request_and_set_approved_quantities(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->requester('calidad');
        $reviewer->givePermissionTo('supply.tab.quality');

        $request = $this->supplyRequest($owner, 'operaciones');
        $item = $request->items()->firstOrFail();

        $response = $this->actingAs($reviewer)->patch(route('supplies.quality.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'approve',
            'quality_observations' => 'Aprobado con ajuste.',
            'items' => [
                $item->id => ['approved_quantity' => 6],
            ],
        ]);

        $response->assertRedirect(route('supplies.quality.index', ['module' => 'operaciones']));
        $this->assertDatabaseHas('supply_requests', [
            'id' => $request->id,
            'status' => 'aprobada_calidad',
            'quality_reviewer_id' => $reviewer->id,
        ]);
        $this->assertDatabaseHas('supply_request_items', [
            'id' => $item->id,
            'approved_quantity' => 6,
        ]);
    }

    public function test_quality_cannot_reprocess_an_already_processed_request(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->requester('calidad');
        $reviewer->givePermissionTo('supply.tab.quality');

        $request = $this->supplyRequest($owner, 'operaciones', 'aprobada_calidad');
        $item = $request->items()->firstOrFail();

        $response = $this->actingAs($reviewer)->patch(route('supplies.quality.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'approve',
            'items' => [
                $item->id => ['approved_quantity' => 1],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_purchasing_completes_request_and_computes_total_cost(): void
    {
        $owner = $this->requester('operaciones');
        $buyer = $this->requester('compras');
        $buyer->givePermissionTo('supply.tab.purchasing');

        $request = $this->supplyRequest($owner, 'operaciones', 'aprobada_calidad');
        $item = $request->items()->firstOrFail();
        $item->update(['approved_quantity' => 5]);

        $response = $this->actingAs($buyer)->patch(route('supplies.purchasing.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'items' => [
                $item->id => [
                    'unit_cost' => 1000,
                    'purchasing_observations' => 'Compra realizada.',
                ],
            ],
        ]);

        $response->assertRedirect(route('supplies.purchasing.index', ['module' => 'operaciones']));
        $this->assertDatabaseHas('supply_requests', [
            'id' => $request->id,
            'status' => 'completada',
            'purchasing_manager_id' => $buyer->id,
            'total_cost' => 5000,
        ]);
    }

    public function test_quality_index_only_shows_pending_requests_for_current_module(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->requester('calidad');
        $reviewer->givePermissionTo('supply.tab.quality');

        $pendingOperaciones = $this->supplyRequest($owner, 'operaciones');
        $comercialPending = $this->supplyRequest($owner, 'comercial');
        $this->supplyRequest($owner, 'operaciones', 'aprobada_calidad');

        $response = $this->actingAs($reviewer)->get(route('supplies.quality.index', ['module' => 'operaciones']));

        $response->assertOk();
        $response->assertSee('>#'.$pendingOperaciones->id.'<', false);
        $response->assertDontSee('>#'.$comercialPending->id.'<', false);
    }

    public function test_purchasing_index_only_shows_requests_for_current_module(): void
    {
        $owner = $this->requester('operaciones');
        $buyer = $this->requester('compras');
        $buyer->givePermissionTo('supply.tab.purchasing');

        $operacionesRequest = $this->supplyRequest($owner, 'operaciones', 'aprobada_calidad');
        $comercialRequest = $this->supplyRequest($owner, 'comercial', 'aprobada_calidad');

        $response = $this->actingAs($buyer)->get(route('supplies.purchasing.index', ['module' => 'operaciones']));

        $response->assertOk();
        $response->assertSee('>#'.$operacionesRequest->id.'<', false);
        $response->assertDontSee('>#'.$comercialRequest->id.'<', false);
    }

    public function test_default_supply_board_url_uses_first_authorized_tab(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.quality');

        $this->assertSame(
            route('supplies.quality.index', ['module' => 'calidad']),
            $user->defaultSupplyBoardUrl('calidad')
        );
    }

    public function test_dashboard_redirects_to_default_supply_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.calidad.suministros');
        $user->givePermissionTo('supply.tab.quality');

        $response = $this->actingAs($user)->get(route('dashboard', [
            'module' => 'calidad',
            'board' => 'suministros',
        ]));

        $response->assertRedirect($user->defaultSupplyBoardUrl('calidad'));
    }

    public function test_store_notifies_quality_reviewers(): void
    {
        Mail::fake();

        $requester = $this->requester('operaciones');
        $reviewer = User::factory()->create([
            'area_key' => 'calidad',
            'email' => 'calidad.reviewer@example.com',
            'must_change_password' => false,
        ]);
        $reviewer->assignRole('usuario');
        $reviewer->givePermissionTo('supply.tab.quality');

        $product = SupplyProduct::query()->firstOrFail();

        $this->actingAs($requester)->post(route('supplies.store', ['module' => 'operaciones']), [
            'observations' => 'Pedido mensual.',
            'items' => [
                [
                    'product_id' => $product->id,
                    'current_inventory' => 1,
                    'quantity' => 5,
                ],
            ],
        ]);

        Mail::assertQueued(SupplyRequestNotification::class, function (SupplyRequestNotification $mail) use ($reviewer) {
            return $mail->hasTo($reviewer->email);
        });
    }

    private function requester(string $areaKey): User
    {
        $user = User::factory()->create([
            'area_key' => $areaKey,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        return $user;
    }

    private function supplyRequest(User $user, string $areaKey, string $status = 'pendiente_calidad'): SupplyRequest
    {
        $product = SupplyProduct::query()->firstOrFail();

        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => $areaKey,
            'status' => $status,
            'observations' => 'Solicitud de prueba.',
        ]);

        $request->items()->create([
            'supply_product_id' => $product->id,
            'current_inventory' => 1,
            'requested_quantity' => 8,
        ]);

        return $request;
    }
}
