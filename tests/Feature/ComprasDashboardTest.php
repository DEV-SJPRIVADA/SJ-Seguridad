<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprasDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_compras_dashboard_renders_for_processing_user(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'view.dashboard',
            'purchase.tab.processing',
            'view.board.compras.dashboard',
            'view.board.compras.bandeja_compras',
        ]);

        $response = $this->actingAs($user)->get(route('compras.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Compras');
        $response->assertSee('id="compras-chart-data"', false);
        $response->assertSee('id="trendChart"', false);
        $response->assertSee('id="bandejaStatusChart"', false);
        $response->assertSee('compras-dashboard-charts', false);
    }

    public function test_guest_cannot_access_compras_dashboard(): void
    {
        $this->get(route('compras.dashboard'))->assertRedirect();
    }

    public function test_user_without_compras_scope_gets_forbidden(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo('purchase.tab.create');

        $this->actingAs($user)->get(route('compras.dashboard'))->assertForbidden();
    }

    public function test_navigation_links_compras_dashboard_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'view.dashboard',
            'purchase.tab.processing',
            'view.board.compras.dashboard',
            'view.board.compras.bandeja_compras',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', ['module' => 'compras', 'board' => 'dashboard']))
            ->assertRedirect(route('compras.dashboard'));
    }
}
