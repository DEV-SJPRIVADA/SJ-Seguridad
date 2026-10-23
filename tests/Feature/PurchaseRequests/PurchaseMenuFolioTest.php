<?php

declare(strict_types=1);

namespace Tests\Feature\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Services\Navigation\NavigationResolver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-048 / T4 — menú Pedidos, Órdenes y Catálogos, y folio visible SC-/SU-.
 */
class PurchaseMenuFolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_processing_user_sees_pedidos_and_not_the_bandeja_tab(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'purchase.tab.processing',
            'purchase.tab.create',
            'view.board.compras.solicitudes_compra',
        ]);

        $nav = app(NavigationResolver::class)->resolve($user, 'dashboard', 'compras');
        $labels = collect(collect($nav['appNavigation'])->firstWhere('key', 'compras')['items'] ?? [])
            ->pluck('label')
            ->all();

        $this->assertContains('Pedidos', $labels);
        $this->assertContains('Órdenes', $labels);
        $this->assertSame('Pedidos', config('access.boards.bandeja_compras'));
        $this->assertSame('Órdenes', config('access.boards.ordenes_compra'));
        $this->assertSame('Catálogos', config('access.boards.proveedores'));

        $tabs = $user->purchaseBoardTabsFor('compras');
        $this->assertTrue($tabs->contains('nueva'));
        $this->assertFalse($tabs->contains('bandeja_compras'));
    }

    public function test_processing_routes_mark_pedidos_not_solicitudes(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo('purchase.tab.processing');

        $nav = app(NavigationResolver::class)->resolve(
            $user,
            'purchase-requests.processing.index',
            'compras',
        );

        $boards = collect(collect($nav['appNavigation'])->firstWhere('key', 'compras')['items'] ?? []);
        $active = $boards->filter(fn (array $board): bool => $board['active'])->pluck('label')->all();

        $this->assertSame(['Pedidos'], $active);
        $this->assertFalse($boards->contains(fn (array $board): bool => $board['active'] && $board['label'] === 'Solicitudes de compra'));
    }

    public function test_quality_user_does_not_see_approved_supplies_tab_and_old_url_redirects(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'view.board.calidad.suministros',
            'supply.tab.quality',
        ]);

        $tabs = $user->supplyBoardTabsFor('calidad');

        $this->assertSame(['aprobacion_insumos'], $tabs->all());
        $this->assertFalse($tabs->contains('insumos_aprobados'));
        $this->assertFalse($tabs->contains('catalogo'));
        $this->assertSame(
            route('supplies.approval.index', ['module' => 'calidad']),
            $user->defaultSupplyBoardUrl('calidad'),
        );

        $this->actingAs($user)
            ->get(route('supplies.approved.index', ['module' => 'calidad']))
            ->assertRedirect(route('supplies.approval.index', ['module' => 'calidad']));
    }

    public function test_catalog_board_shows_providers_or_insumos_by_permission(): void
    {
        $providers = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $providers->givePermissionTo('purchase.providers.view');

        $this->assertSame(['proveedores', 'iva'], $providers->catalogBoardTabs()->all());

        $editor = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $editor->givePermissionTo('purchase.providers.edit');

        $this->assertSame(['proveedores', 'iva'], $editor->catalogBoardTabs()->all());

        $insumos = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $insumos->givePermissionTo('supply.tab.catalog');

        $this->assertSame(['insumos'], $insumos->catalogBoardTabs()->all());

        $nav = app(NavigationResolver::class)->resolve(
            $insumos,
            'supplies.products.index',
            'compras',
        );
        $boards = collect(collect($nav['appNavigation'])->firstWhere('key', 'compras')['items'] ?? []);
        $active = $boards->filter(fn (array $board): bool => $board['active'])->pluck('label')->all();

        $this->assertSame(['Catálogos'], $active);
        $this->assertFalse($boards->contains(fn (array $board): bool => ($board['label'] ?? '') === 'Suministros'));
    }

    public function test_purchase_folio_visible_prefixes_four_digits_without_trimming_larger_numbers(): void
    {
        $purchase = new PurchaseRequest(['numero_solicitud' => 7]);

        $this->assertSame('0007', $purchase->folio());
        $this->assertSame('SC-0007', $purchase->folioVisible());

        $purchase->numero_solicitud = 10000;

        $this->assertSame('10000', $purchase->folio());
        $this->assertSame('SC-10000', $purchase->folioVisible());

        $supply = new SupplyRequest;
        $supply->id = 12;

        $this->assertSame('SU-0012', $supply->folioVisible());

        $supply->id = 10000;

        $this->assertSame('SU-10000', $supply->folioVisible());
    }
}
