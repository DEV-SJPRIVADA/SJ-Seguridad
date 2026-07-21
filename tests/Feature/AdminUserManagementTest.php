<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Admin\UserPermissionFormBuilder;
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
        $response->assertSee('Procesos');
        $response->assertSee('Administracion');
        $response->assertSee('Usuarios');
        $response->assertSee('Nuevo usuario');
    }

    public function test_admin_user_form_uses_three_permission_sections_without_presets_or_preview(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $response = $this->actingAs($admin)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee('En su area asignada');
        $response->assertSee('Funcionalidades transversales');
        $response->assertSee('Otras areas');
        $response->assertSee('Solicitar requisiciones de personal');
        $response->assertSee('Gestion humana');
        $response->assertSee('Comercial');
        $response->assertDontSee('Plantilla de perfil');
        $response->assertDontSee('Vista previa del menu');
        $response->assertDontSee('value="manage.requisitions"', false);
    }

    public function test_permission_form_builder_lists_global_requisition_actions_once(): void
    {
        $form = app(UserPermissionFormBuilder::class)->build();
        $requisitionGroup = collect($form['sections']['global']['groups'] ?? [])
            ->firstWhere('key', 'requisitions');

        $this->assertNotNull($requisitionGroup);
        $names = collect($requisitionGroup['permissions'])->pluck('name')->all();
        $this->assertContains('requisitions.tab.gestion', $names);
        $this->assertContains('manage.requisition.parameters', $names);
        $this->assertCount(3, $names);
    }

    public function test_user_with_board_permission_sees_its_module_and_tab(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.gestion_humana.dashboard');

        $response = $this->actingAs($user)->get(route('dashboard', ['module' => 'gestion_humana', 'board' => 'dashboard']));

        $response->assertOk();
        $response->assertSee('Gestion humana');
        $response->assertSee('Tablero activo');
        $response->assertSee('Dashboard');
    }

    public function test_user_without_module_query_is_redirected_to_first_authorized_board(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.gestion_humana.dashboard');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('dashboard', [
            'module' => 'gestion_humana',
            'board' => 'dashboard',
        ]));
    }

    public function test_user_with_area_permission_gets_default_documents_board(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');
        $user->givePermissionTo('view.area.gestion_humana');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('quality-documents.library.index', ['module' => 'gestion_humana']));
    }

    public function test_admin_user_index_lists_only_active_users_by_default(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $activeUser = User::factory()->create([
            'name' => 'Usuario Activo Visible',
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $activeUser->assignRole('usuario');

        $inactiveUser = User::factory()->create([
            'name' => 'Usuario Inactivo Oculto',
            'must_change_password' => false,
            'is_active' => false,
        ]);
        $inactiveUser->assignRole('usuario');

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Usuario Activo Visible');
        $response->assertDontSee('Usuario Inactivo Oculto');
    }

    public function test_admin_user_index_can_include_inactive_users_with_checkbox_filter(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $inactiveUser = User::factory()->create([
            'name' => 'Usuario Inactivo Visible',
            'must_change_password' => false,
            'is_active' => false,
        ]);
        $inactiveUser->assignRole('usuario');

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'include_inactive' => '1',
        ]));

        $response->assertOk();
        $response->assertSee('Mostrar usuarios inactivos');
        $response->assertSee('Usuario Inactivo Visible');
    }

    public function test_admin_user_index_shows_flat_assigned_permissions_without_section_groups(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $user = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'operaciones',
        ]);
        $user->assignRole('usuario');
        $user->syncPermissions([
            'requisitions.tab.solicitar',
            'requisitions.tab.gestion',
            'view.board.gestion_humana.requisiciones',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['selected' => $user->id]));

        $response->assertOk();
        $response->assertSee('Permisos asignados');
        $response->assertSee('Solicitar requisiciones de personal');
        $response->assertSee('Requisiciones: Gestion de Solicitudes');
        $response->assertSee('Ver Requisiciones (Gestion humana)');
        $response->assertDontSee('Transversales');
        $response->assertDontSee('Otras areas');
        $response->assertDontSee('En su area');
    }

    public function test_admin_can_update_user_permissions_without_setting_new_password(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $user = User::factory()->create([
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => 'usuario',
            'is_active' => '1',
            'must_change_password' => '0',
            'permissions' => ['view.board.gestion_humana.dashboard'],
        ]);

        $response->assertRedirect(route('admin.users.edit', $user));
        $this->assertTrue($user->fresh()->can('view.board.gestion_humana.dashboard'));
    }
}
