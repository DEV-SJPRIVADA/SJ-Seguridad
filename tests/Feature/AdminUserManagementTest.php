<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Usuario Operativo',
            'email' => 'operativo@example.com',
            'password' => 'Temporal123!',
            'role' => 'usuario',
            'is_active' => '1',
            'must_change_password' => '1',
            'permissions' => ['view.area.gestion_humana', 'view.board.gestion_humana.dashboard'],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'operativo@example.com',
            'is_active' => true,
            'must_change_password' => true,
        ]);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_sees_sidebar_modules_and_horizontal_tabs_by_permission(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Modulos autorizados');
        $response->assertSee('Administracion');
        $response->assertSee('Usuarios');
        $response->assertSee('Nuevo usuario');
    }

    public function test_user_with_board_permission_sees_its_module_and_tab(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.gestion_humana.dashboard');

        $response = $this->actingAs($user)->get(route('dashboard', ['module' => 'gestion_humana', 'board' => 'dashboard']));

        $response->assertOk();
        $response->assertSee('Gestion humana');
        $response->assertSee('Dashboard');
        $response->assertSee('Tablero activo: Dashboard');
    }
}
