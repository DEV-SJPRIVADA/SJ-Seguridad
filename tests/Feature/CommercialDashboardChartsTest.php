<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialDashboardChartsTest extends TestCase
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
            'area_key' => 'comercial',
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('comercial.matriz.view');

        $response = $this->actingAs($user)->get(route('comercial.dashboard'));

        $response->assertOk();
        $response->assertSee('id="comercial-chart-data"', false);
        $response->assertSee('id="trendChart"', false);
        $response->assertSee('id="portfolioChart"', false);
        $response->assertSee('id="cityChart"', false);
        $response->assertSee('id="serviceTypeChart"', false);
        $response->assertSee('comercial-dashboard-charts', false);
        $response->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false);
        $response->assertDontSee('new Chart(', false);
    }
}
