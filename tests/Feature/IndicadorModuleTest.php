<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class IndicadorModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionCatalog::sync();
        $this->seed(\Database\Seeders\IndicadorSeeder::class);
        $this->seed(\Database\Seeders\DashboardWeightSeeder::class);
    }

    public function test_guest_cannot_access_indicadores_dashboard(): void
    {
        $this->get(route('indicadores.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_without_operations_permissions_gets_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo('view.dashboard');

        $this->actingAs($user)->get(route('indicadores.dashboard'))->assertForbidden();
    }

    public function test_operations_view_user_can_access_dashboard(): void
    {
        $user = $this->operationsViewer();

        $this->actingAs($user)->get(route('indicadores.dashboard'))->assertOk();
    }

    public function test_operations_capture_user_can_access_captura_list(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.capture']);

        $this->actingAs($user)->get(route('indicadores.index'))->assertOk();
    }

    public function test_operations_manage_user_can_access_ajustes(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $this->actingAs($user)
            ->get(route('indicadores.admin.ajustes'))
            ->assertOk()
            ->assertSee('Ajustes de indicadores')
            ->assertSee('Periodos de captura');
    }

    public function test_legacy_periodos_route_redirects_to_ajustes(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $this->actingAs($user)
            ->get(route('indicadores.admin.periods.index'))
            ->assertRedirect(route('indicadores.admin.ajustes', ['section' => 'periodos']));
    }

    public function test_dashboard_redirects_to_indicadores_when_board_selected(): void
    {
        $user = $this->operationsViewer();
        Permission::findOrCreate('view.area.operaciones', 'web');
        $user->givePermissionTo('view.area.operaciones');

        $this->actingAs($user)
            ->get(route('dashboard', ['module' => 'operaciones', 'board' => 'indicadores']))
            ->assertRedirect(route('indicadores.dashboard'));
    }

    public function test_operations_manage_user_can_access_mother_show(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.manage']);

        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $this->actingAs($user)
            ->get(route('indicadores.admin.mother.show', ['indicator' => $indicator->code, 'year' => 2026, 'month' => 7]))
            ->assertOk()
            ->assertSee('Consolidado — FT-OP-01');
    }

    public function test_operations_export_user_can_download_capture_excel(): void
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.export', 'operations.capture']);

        $indicator = \App\Models\Indicator::query()->where('code', 'FT-OP-01')->firstOrFail();

        $response = $this->actingAs($user)->get(route('indicadores.export.leader.excel', [
            'indicator' => $indicator->code,
            'year' => now()->year,
            'month' => now()->month,
            'user_id' => $user->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function operationsViewer(): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->givePermissionTo(['view.dashboard', 'operations.view']);

        return $user;
    }
}
