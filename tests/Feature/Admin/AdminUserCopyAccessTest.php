<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AdminUserCopyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_create_form_prefills_access_from_copy_from_query(): void
    {
        $admin = $this->adminUser();

        $source = User::factory()->create([
            'name' => 'Usuario Saliente',
            'must_change_password' => false,
            'is_active' => false,
            'area_key' => 'gestion_humana',
        ]);
        $source->assignRole('usuario');
        $source->syncPermissions([
            'view.area.gestion_humana',
            'view.board.gestion_humana.dashboard',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.create', [
            'copy_from' => $source->id,
            'include_area' => '1',
            'include_sede' => '1',
            'tab' => 'capabilities',
        ]));

        $response->assertOk();
        $response->assertSee('Copiar acceso de otro usuario');
        $response->assertSee('Acceso precargado desde');
        $response->assertSee('Usuario Saliente');
        $response->assertSee('value="gestion_humana"', false);
        $response->assertSee('view.board.gestion_humana.dashboard', false);
        $response->assertSee('value="usuario"', false);
        $response->assertSee('selected', false);
    }

    public function test_create_form_does_not_nest_copy_access_form_inside_post_form(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.users.create'));

        $response->assertOk();

        $html = $response->getContent();
        $mainFormStart = strpos($html, 'id="user-permissions-form"');
        $mainFormEnd = strpos($html, '</form>', $mainFormStart);

        $this->assertNotFalse($mainFormStart);
        $this->assertNotFalse($mainFormEnd);

        $mainFormChunk = substr($html, $mainFormStart, $mainFormEnd - $mainFormStart);
        $this->assertStringNotContainsString('<form', $mainFormChunk);
    }

    public function test_admin_can_apply_access_from_one_user_to_another(): void
    {
        $admin = $this->adminUser();

        $source = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'gestion_humana',
        ]);
        $source->assignRole('usuario');
        $source->syncPermissions([
            'view.area.gestion_humana',
            'view.board.gestion_humana.dashboard',
        ]);

        $target = User::factory()->create([
            'must_change_password' => false,
            'area_key' => 'compras',
        ]);
        $target->assignRole('usuario');
        $target->syncPermissions(['view.area.compras']);

        $response = $this->actingAs($admin)->post(route('admin.users.apply-access', $target), [
            'source_user_id' => $source->id,
            'include_area' => '1',
            'include_sede' => '1',
        ]);

        $response->assertRedirect(route('admin.users.edit', $target));
        $response->assertSessionHas('status', 'access-applied');

        $target->refresh();
        $target->load('permissions');

        $this->assertSame('gestion_humana', $target->area_key);
        $this->assertTrue($target->can('view.board.gestion_humana.dashboard'));
        $this->assertFalse($target->can('view.area.compras'));
    }

    public function test_apply_access_logs_audit_event(): void
    {
        $admin = $this->adminUser();

        $source = User::factory()->create(['must_change_password' => false]);
        $source->assignRole('usuario');
        $source->syncPermissions(['view.area.operaciones']);

        $target = User::factory()->create(['must_change_password' => false]);
        $target->assignRole('usuario');

        $this->actingAs($admin)->post(route('admin.users.apply-access', $target), [
            'source_user_id' => $source->id,
            'include_area' => '1',
            'include_sede' => '0',
        ])->assertRedirect(route('admin.users.edit', $target));

        $log = AuditLog::query()
            ->where('module', 'admin')
            ->where('event_type', 'user_management')
            ->where('action', 'access_copied')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($source->id, $log->metadata['source_user_id'] ?? null);
    }

    public function test_administrator_cannot_copy_access_from_super_admin(): void
    {
        $superAdmin = $this->adminUser();
        $superAdmin->update(['must_change_password' => false]);

        $administrator = User::factory()->create([
            'email' => 'administrator-copy@example.com',
            'must_change_password' => false,
        ]);
        $administrator->syncRoles(['administrador']);
        $administrator->givePermissionTo('manage.users');

        $target = User::factory()->create(['must_change_password' => false]);
        $target->assignRole('usuario');

        $response = $this->actingAs($administrator)->post(route('admin.users.apply-access', $target), [
            'source_user_id' => $superAdmin->id,
            'include_area' => '1',
            'include_sede' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('source_user_id');
    }

    public function test_cannot_apply_access_from_same_user(): void
    {
        $admin = $this->adminUser();

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');

        $response = $this->actingAs($admin)->post(route('admin.users.apply-access', $user), [
            'source_user_id' => $user->id,
            'include_area' => '1',
            'include_sede' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('source_user_id');
    }

    public function test_edit_form_shows_apply_access_action(): void
    {
        $admin = $this->adminUser();

        $source = User::factory()->create(['must_change_password' => false]);
        $source->assignRole('usuario');

        $target = User::factory()->create(['must_change_password' => false]);
        $target->assignRole('usuario');

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $target));

        $response->assertOk();
        $response->assertSee('Aplicar acceso de otro usuario');
        $response->assertSee($source->name);
        $response->assertDontSee('value="'.$target->id.'"', false);
    }

    private function adminUser(): User
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        return $admin;
    }
}
