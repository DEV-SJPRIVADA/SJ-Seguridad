<?php

namespace Tests\Feature\DevelopmentRequests;

use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentRequestFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_devreq_permissions_exist_in_catalog(): void
    {
        $names = PermissionCatalog::configuredNames();

        $this->assertTrue($names->contains('devreq.tab.create'));
        $this->assertTrue($names->contains('devreq.tab.my_requests'));
        $this->assertTrue($names->contains('devreq.tab.leader_approval'));
        $this->assertTrue($names->contains('devreq.tab.tic_queue'));
        $this->assertTrue($names->contains('devreq.tab.view'));
        $this->assertTrue($names->contains('view.board.tic.solicitudes_desarrollo'));
        $this->assertTrue($names->contains('view.area.tic'));
        $this->assertFalse($names->contains('view.area.Tic'));
        $this->assertFalse($names->contains('view.board.Tic.dashboard'));
    }

    public function test_tic_area_and_board_config(): void
    {
        $this->assertSame('TIC', config('access.areas.tic'));
        $this->assertNull(config('access.areas.Tic'));
        $this->assertSame('Solicitudes desarrollo', config('access.boards.solicitudes_desarrollo'));
        $this->assertSame('tic', config('access.board_canonical_areas.solicitudes_desarrollo.home'));
        $this->assertSame('Nueva solicitud', config('access.devreq_tabs.nueva'));
        $this->assertSame('Bandeja TIC', config('access.devreq_tabs.bandeja_tic'));
    }

    public function test_audit_module_development_requests_is_configured(): void
    {
        $this->assertSame('Solicitudes de desarrollo', config('audit.modules.development_requests.label'));
        $this->assertSame('tic', config('audit.modules.development_requests.area'));
    }

    public function test_index_forbidden_without_permission(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'tic',
        ]);

        $this->actingAs($user)
            ->get(route('development-requests.index', ['module' => 'tic']))
            ->assertForbidden();
    }

    public function test_index_allows_board_and_view_permissions(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'tic',
        ]);
        $user->givePermissionTo([
            'view.board.tic.solicitudes_desarrollo',
            'devreq.tab.view',
            'devreq.tab.my_requests',
        ]);

        $this->actingAs($user)
            ->get(route('development-requests.index', ['module' => 'tic']))
            ->assertRedirect(route('development-requests.my-requests', ['module' => 'tic']));

        $this->actingAs($user)
            ->get(route('development-requests.my-requests', ['module' => 'tic']))
            ->assertOk()
            ->assertSee('Mis solicitudes', false);
    }

    public function test_create_form_loads_for_tic_user_with_create_permission(): void
    {
        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'tic',
            'is_active' => true,
        ]);
        $user->givePermissionTo([
            'devreq.tab.create',
            'devreq.tab.my_requests',
            'view.board.tic.solicitudes_desarrollo',
        ]);

        $this->actingAs($user)
            ->get(route('development-requests.create', ['module' => 'tic']))
            ->assertOk()
            ->assertSee('Nueva solicitud de desarrollo', false)
            ->assertSee('Clasificacion', false)
            ->assertSee('Antes de enviar', false)
            ->assertSee('Enviar / radicar', false);
    }

    public function test_permission_catalog_sync_includes_new_permissions(): void
    {
        $result = PermissionCatalog::sync();

        $this->assertGreaterThan(0, $result['synced']);
        $this->assertDatabaseHas('permissions', [
            'name' => 'devreq.tab.create',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'view.board.tic.solicitudes_desarrollo',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseMissing('permissions', [
            'name' => 'view.area.Tic',
            'guard_name' => 'web',
        ]);
    }
}
