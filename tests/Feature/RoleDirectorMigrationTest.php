<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleDirectorMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_director_role_has_expected_permissions(): void
    {
        $role = Role::findByName('director', 'web');
        $permissionNames = $role->permissions->pluck('name')->all();

        $this->assertContains('purchase.tab.approval', $permissionNames);
        $this->assertContains('requisitions.approve.management', $permissionNames);
        $this->assertNotContains('manage.users', $permissionNames);
    }

    public function test_administrador_role_does_not_include_manage_users(): void
    {
        $role = Role::findByName('administrador', 'web');
        $permissionNames = $role->permissions->pluck('name')->all();

        $this->assertNotContains('manage.users', $permissionNames);
    }

    public function test_super_admin_has_manage_users(): void
    {
        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('super-admin');

        $this->assertTrue($admin->can('manage.users'));
    }
}
