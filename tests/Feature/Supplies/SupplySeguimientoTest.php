<?php

namespace Tests\Feature\Supplies;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderEvaluation;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseProvider;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\Navigation\NavigationResolver;
use App\Services\PurchaseRequests\PurchaseOrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplySeguimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_seguimiento_permission_opens_historial_and_gasto_in_compras(): void
    {
        $user = $this->seguimientoUser();

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.historial', ['module' => 'compras']))
            ->assertOk()
            ->assertSee('Historial de suministros')
            ->assertSee('Órdenes')
            ->assertSee('Gasto');

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.gasto', ['module' => 'compras']))
            ->assertOk()
            ->assertSee('Gasto de suministros');
    }

    public function test_user_without_permission_cannot_open_seguimiento(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.historial', ['module' => 'compras']))
            ->assertForbidden();
    }

    public function test_seguimiento_is_not_available_outside_compras(): void
    {
        $user = $this->seguimientoUser();

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.historial', ['module' => 'calidad']))
            ->assertForbidden();
    }

    public function test_seguimiento_user_sees_suministros_only_in_compras(): void
    {
        $user = $this->seguimientoUser('operaciones');

        $nav = app(NavigationResolver::class)->resolve($user, 'dashboard');
        $areaKeys = collect($nav['appNavigation'])->pluck('key')->all();

        $this->assertContains('compras', $areaKeys);
        $this->assertNotContains('calidad', $areaKeys);

        $comprasBoards = collect(collect($nav['appNavigation'])->firstWhere('key', 'compras')['items'] ?? [])
            ->pluck('label')
            ->all();
        $this->assertContains(config('access.boards.suministros'), $comprasBoards);

        $this->assertSame(
            route('supplies.seguimiento.historial', ['module' => 'compras']),
            $user->defaultSupplyBoardUrl('compras')
        );
        $this->assertSame(
            ['seguimiento_historial', 'seguimiento_ordenes', 'seguimiento_gasto'],
            $user->supplyBoardTabsFor('compras')->all()
        );
    }

    public function test_historial_only_includes_quality_authorized_requests_and_sums_quantities(): void
    {
        $owner = $this->requester();
        $product = SupplyProduct::query()->firstOrFail();
        $site = $owner->site;

        $this->authorizedRequest($owner, $product, $site, '2026-03-05', requested: 10, authorized: 8, inventory: 2);
        $this->authorizedRequest($owner, $product, $site, '2026-03-20', requested: 4, authorized: 4, inventory: 7);
        $this->pendingRequest($owner, $product, $site, '2026-03-15', requested: 99, inventory: 50);

        $user = $this->seguimientoUser();

        $html = $this->actingAs($user)
            ->get(route('supplies.seguimiento.historial', ['module' => 'compras', 'year' => 2026]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($product->name, $html);
        $this->assertStringContainsString('title="Inventario"', $html);
        $this->assertStringContainsString('title="Solicitud"', $html);
        $this->assertStringContainsString('>14</td>', $html);
        $this->assertStringContainsString('>12</td>', $html);
        $this->assertStringContainsString('>7</td>', $html);
        $this->assertStringNotContainsString('>99</td>', $html);
    }

    public function test_historial_includes_custom_products_and_filters_by_area(): void
    {
        $owner = $this->requester('operaciones');
        $other = $this->requester('calidad');
        $product = SupplyProduct::query()->firstOrFail();
        $this->authorizedCustomRequest($owner, '2026-04-02', 'GUANTES ESPECIALES', 3, 2);
        $this->authorizedRequest($other, $product, $other->site, '2026-04-02', 5, 5, 1);

        $user = $this->seguimientoUser();

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.historial', [
                'module' => 'compras',
                'year' => 2026,
                'area_key' => 'operaciones',
            ]))
            ->assertOk()
            ->assertSee('GUANTES ESPECIALES')
            ->assertDontSee($product->name)
            ->assertSee('Todos los productos');

        $customKey = 'c:'.rtrim(strtr(base64_encode('GUANTES ESPECIALES'), '+/', '-_'), '=');

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.historial', [
                'module' => 'compras',
                'year' => 2026,
                'product' => $customKey,
            ]))
            ->assertOk()
            ->assertSee('GUANTES ESPECIALES');
    }

    public function test_product_orders_list_emitted_orders_and_gasto_only_counts_received(): void
    {
        $owner = $this->requester();
        $product = SupplyProduct::query()->firstOrFail();
        $supply = $this->authorizedRequest($owner, $product, $owner->site, '2026-05-01', 10, 10, 0);
        $item = $supply->items()->firstOrFail();

        $received = $this->supplyOrder($supply, $item, '2026-05-10', 6, 1000, 1140, receivedAt: '2026-05-12');
        $this->supplyOrder($supply, $item, '2026-05-18', 4, 1000, 760, receivedAt: null);
        $this->supplyOrder($supply, $item, '2026-06-03', 3, 1000, 570, receivedAt: null);

        $user = $this->seguimientoUser();
        $productKey = 'p:'.$product->id;

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.historial', [
                'module' => 'compras',
                'year' => 2026,
                'product' => $productKey,
            ]))
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee('Órdenes de suministros')
            ->assertDontSee('Folio suministro');

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.ordenes', [
                'module' => 'compras',
                'year' => 2026,
                'product' => $productKey,
            ]))
            ->assertOk()
            ->assertSee((string) $received->id)
            ->assertSee('03/06/26')
            ->assertSee('Recibida')
            ->assertSee('Emitida')
            ->assertSee('Totales')
            ->assertSee('13')
            ->assertSee('$ 15.470,00');

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.ordenes', [
                'module' => 'compras',
                'year' => 2026,
                'month' => 5,
                'product' => $productKey,
            ]))
            ->assertOk()
            ->assertSee('en Mayo de 2026')
            ->assertSee('10/05/26')
            ->assertDontSee('03/06/26')
            ->assertSee('$ 11.900,00');

        $gasto = $this->actingAs($user)
            ->get(route('supplies.seguimiento.gasto', ['module' => 'compras', 'year' => 2026]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('title="Comprado"', $gasto);
        $this->assertStringContainsString('>6</td>', $gasto);
        $this->assertStringContainsString('$ 7.140,00', $gasto);
    }

    public function test_historial_excel_downloads_for_authorized_user(): void
    {
        $user = $this->seguimientoUser();

        $this->actingAs($user)
            ->get(route('supplies.seguimiento.historial.export', ['module' => 'compras', 'year' => 2026]))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_super_admin_can_open_seguimiento(): void
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $this->actingAs($admin)
            ->get(route('supplies.seguimiento.historial', ['module' => 'compras']))
            ->assertOk();
    }

    private function seguimientoUser(string $areaKey = 'compras'): User
    {
        $user = User::factory()->create([
            'area_key' => $areaKey,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.seguimiento');

        return $user;
    }

    private function requester(string $areaKey = 'operaciones'): User
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $user = User::factory()->create([
            'area_key' => $areaKey,
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        return $user;
    }

    private function authorizedRequest(
        User $user,
        SupplyProduct $product,
        SupplySite $site,
        string $createdAt,
        int $requested,
        int $authorized,
        int $inventory,
    ): SupplyRequest {
        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => $user->area_key,
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'aprobada_calidad',
        ]);
        $request->forceFill(['created_at' => $createdAt.' 10:00:00'])->saveQuietly();
        $request->items()->create([
            'supply_product_id' => $product->id,
            'current_inventory' => $inventory,
            'requested_quantity' => $requested,
            'approved_quantity' => $authorized,
            'is_not_in_catalog' => false,
        ]);

        return $request->fresh(['items']);
    }

    private function pendingRequest(
        User $user,
        SupplyProduct $product,
        SupplySite $site,
        string $createdAt,
        int $requested,
        int $inventory,
    ): void {
        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => $user->area_key,
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'pendiente_calidad',
        ]);
        $request->forceFill(['created_at' => $createdAt.' 10:00:00'])->saveQuietly();
        $request->items()->create([
            'supply_product_id' => $product->id,
            'current_inventory' => $inventory,
            'requested_quantity' => $requested,
            'is_not_in_catalog' => false,
        ]);
    }

    private function authorizedCustomRequest(
        User $user,
        string $createdAt,
        string $name,
        int $requested,
        int $authorized,
    ): void {
        $site = $user->site;
        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => $user->area_key,
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'aprobada_calidad',
        ]);
        $request->forceFill(['created_at' => $createdAt.' 10:00:00'])->saveQuietly();
        $request->items()->create([
            'custom_product_name' => $name,
            'is_not_in_catalog' => true,
            'current_inventory' => 0,
            'requested_quantity' => $requested,
            'approved_quantity' => $authorized,
        ]);
    }

    private function supplyOrder(
        SupplyRequest $supply,
        object $item,
        string $fecha,
        int $cantidad,
        float $valorUnidad,
        float $iva,
        ?string $receivedAt,
    ): PurchaseOrder {
        $order = PurchaseOrder::factory()->create([
            'source_type' => PurchaseOrderService::SOURCE_SUPPLY,
            'source_id' => $supply->id,
            'fecha' => $fecha,
            'estado' => PurchaseOrder::ESTADO_EMITIDA,
            'total' => ($cantidad * $valorUnidad) + $iva,
            'created_by' => User::factory(),
            'provider_id' => PurchaseProvider::factory(),
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'cantidad' => $cantidad,
            'valor_unidad' => $valorUnidad,
            'iva' => $iva,
            'valor_total' => ($cantidad * $valorUnidad) + $iva,
            'source_item_type' => PurchaseOrderService::SOURCE_ITEM_SUPPLY,
            'source_item_id' => $item->id,
        ]);

        if ($receivedAt !== null) {
            PurchaseOrderEvaluation::factory()->create([
                'purchase_order_id' => $order->id,
                'fecha_recibido' => $receivedAt,
            ]);
        }

        return $order;
    }
}
