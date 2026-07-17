<?php

namespace Tests\Feature;

use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthorizationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_dashboard_requires_view_dashboard_permission(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->syncRoles([]);
        $user->syncPermissions(['view.area.operaciones']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_supply_tab_my_requests_works_in_base_area_without_board_scope(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        $this->actingAs($user)
            ->get(route('supplies.index', ['module' => 'calidad']))
            ->assertOk();
    }

    public function test_supply_board_alone_does_not_grant_my_requests_access(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.calidad.suministros');

        $this->actingAs($user)
            ->get(route('supplies.index', ['module' => 'calidad']))
            ->assertForbidden();
    }

    public function test_supply_quality_tab_requires_visible_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'view.board.calidad.suministros',
            'approve.supply.quality',
        ]);

        $this->actingAs($user)
            ->get(route('supplies.approval.index', ['module' => 'calidad']))
            ->assertOk();
    }

    public function test_supply_request_idor_returns_not_found_for_wrong_module(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => 'operaciones',
            'status' => 'pendiente_calidad',
        ]);

        $this->actingAs($user)
            ->get(route('supplies.show', ['module' => 'calidad', 'supply_request' => $request->id]))
            ->assertNotFound();
    }

    public function test_must_change_password_blocks_supply_routes(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => true,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        $this->actingAs($user)
            ->get(route('supplies.index', ['module' => 'operaciones']))
            ->assertRedirect(route('profile.edit'));
    }

    public function test_quality_admin_routes_require_calidad_module(): void
    {
        $manager = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('manage.quality.documents');

        $this->actingAs($manager)
            ->get(route('quality-documents.admin.index', ['module' => 'operaciones']))
            ->assertNotFound();
    }

    public function test_sync_permissions_command_excludes_documentos_board_permissions(): void
    {
        Permission::findOrCreate('view.board.operaciones.documentos', 'web');

        Artisan::call('app:sync-permissions');

        $this->assertDatabaseMissing('permissions', [
            'name' => 'view.board.operaciones.documentos',
        ]);
    }
}
