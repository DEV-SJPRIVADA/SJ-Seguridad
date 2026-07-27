<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequisitionsDashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_dashboard_renders_apexcharts_containers_without_chartjs_cdn(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'gestion_humana',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'view.board.gestion_humana.requisiciones',
            'requisitions.tab.dashboard',
        ]);

        $response = $this->actingAs($user)->get(route('requisitions.dashboard', [
            'module' => 'gestion_humana',
            'year' => now()->year,
        ]));

        $response->assertOk();
        $response->assertSee('id="requisitions-chart-data"', false);
        $response->assertSee('id="trendChart"', false);
        $response->assertSee('id="statusChart"', false);
        $response->assertSee('id="cityChart"', false);
        $response->assertSee('id="clientChart"', false);
        $response->assertSee('requisitions-dashboard-charts', false);
        $response->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false);
        $response->assertDontSee('new Chart(', false);
        $response->assertDontSee('<canvas', false);
    }
}
