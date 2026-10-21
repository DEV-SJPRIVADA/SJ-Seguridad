<?php

declare(strict_types=1);

namespace Tests\Feature\PurchaseRequests;

use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-048 / T3 — el precio del suministro vive en la OC, no en el formulario de procesar.
 */
class PurchaseSupplyPriceTest extends TestCase
{
    use RefreshDatabase;

    private User $compras;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->compras = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $this->compras->givePermissionTo('purchase.tab.processing');
    }

    public function test_processing_a_supply_does_not_require_unit_cost(): void
    {
        $supply = $this->supplyWithCost(25);
        $item = $supply->items->first();

        $this->actingAs($this->compras)->get(route('purchase-requests.processing.supply', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]))
            ->assertOk()
            ->assertSee('Productos del suministro')
            ->assertDontSee('Costo unitario')
            ->assertDontSee('unit_cost', false)
            ->assertDontSee('Guardar costos');

        $this->actingAs($this->compras)->patch(route('purchase-requests.processing.supply.update', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]), [
            'action' => 'save',
            'items' => [
                $item->id => [],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('aprobada_calidad', $supply->fresh()->status);
        $this->assertSame('25.00', (string) $item->fresh()->unit_cost);
    }

    public function test_saving_processing_does_not_clear_or_replace_an_existing_unit_cost(): void
    {
        $supply = $this->supplyWithCost(25);
        $item = $supply->items->first();

        $this->actingAs($this->compras)->patch(route('purchase-requests.processing.supply.update', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]), [
            'action' => 'complete',
            'items' => [
                $item->id => ['unit_cost' => null],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('25.00', (string) $item->fresh()->unit_cost);

        $this->actingAs($this->compras)->patch(route('purchase-requests.processing.supply.update', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]), [
            'action' => 'save',
            'items' => [
                $item->id => ['unit_cost' => 999],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('25.00', (string) $item->fresh()->unit_cost);
        $this->assertSame('aprobada_calidad', $supply->fresh()->status);
    }

    public function test_processing_saves_purchasing_observations_in_uppercase_without_action_field(): void
    {
        $supply = $this->supplyWithCost(25);
        $item = $supply->items->first();

        $this->actingAs($this->compras)->patch(route('purchase-requests.processing.supply.update', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]), [
            'purchasing_observations' => [
                $item->id => 'cotizar con dos proveedores',
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('COTIZAR CON DOS PROVEEDORES', $item->fresh()->purchasing_observations);
        $this->assertSame('aprobada_calidad', $supply->fresh()->status);
    }

    public function test_supply_order_form_preloads_valor_unidad_from_historical_unit_cost(): void
    {
        $supply = $this->supplyWithCost(25);
        $item = $supply->items->first();

        $response = $this->actingAs($this->compras)->get(route('purchase-requests.orders.supply.create', [
            'module' => 'compras',
            'supply_request' => $supply->id,
        ]));

        $response->assertOk();
        $response->assertSee('Valor unidad', false);
        $this->assertMatchesRegularExpression(
            '/name="items\[0\]\[valor_unidad\]"[^>]*value="25"/',
            $response->getContent(),
        );

        $this->assertSame('25.00', (string) $item->fresh()->unit_cost);
    }

    private function supplyWithCost(float $unitCost): SupplyRequest
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
            'requested_quantity' => 4,
            'approved_quantity' => 4,
            'current_inventory' => 0,
            'unit_cost' => $unitCost,
            'is_not_in_catalog' => false,
        ]);

        return $supply->load('items');
    }
}
