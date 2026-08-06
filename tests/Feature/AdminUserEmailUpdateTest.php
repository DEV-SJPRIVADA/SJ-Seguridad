<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Admin\UserPermissionFormBuilder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserEmailUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_update_user_email_without_password(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $user = User::factory()->create([
            'email' => 'original@example.com',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'document_number' => $user->document_number,
            'email' => 'updated@example.com',
            'password' => '',
            'role' => 'usuario',
            'is_active' => '1',
            'must_change_password' => '0',
        ]);

        $response->assertRedirect(route('admin.users.edit', $user));
        $response->assertSessionHasNoErrors();
        $this->assertSame('updated@example.com', $user->fresh()->email);
    }

    public function test_admin_sees_email_validation_error_when_duplicate(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        User::factory()->create(['email' => 'taken@example.com', 'must_change_password' => false]);

        $user = User::factory()->create([
            'email' => 'original@example.com',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');

        $response = $this->actingAs($admin)->from(route('admin.users.edit', $user))->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'document_number' => $user->document_number,
            'email' => 'taken@example.com',
            'password' => '',
            'role' => 'usuario',
            'is_active' => '1',
            'must_change_password' => '0',
        ]);

        $response->assertRedirect(route('admin.users.edit', $user));
        $response->assertSessionHasErrors('email');

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $user));
        $response->assertSee('validation-error-summary', false);
        $response->assertSee('ya esta registrado', false);
    }

    public function test_admin_can_update_user_with_all_form_permissions_checked(): void
    {
        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@sjseguridad.local'))->firstOrFail();
        $admin->update(['must_change_password' => false]);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');

        $permissionNames = collect(app(UserPermissionFormBuilder::class)->build()['sections'] ?? [])
            ->flatMap(function (array $section): array {
                if (isset($section['permissions'])) {
                    return collect($section['permissions'])->pluck('name')->all();
                }

                return collect($section['groups'] ?? [])
                    ->flatMap(fn (array $group) => collect($group['permissions'] ?? [])->pluck('name'))
                    ->merge(collect($section['areas'] ?? [])
                        ->flatMap(function (array $area): array {
                            $subgroups = collect($area['subgroups'] ?? [])->flatMap(
                                fn (array $subgroup) => collect($subgroup['permissions'] ?? [])->pluck('name')
                            );

                            if ($subgroups->isNotEmpty()) {
                                return $subgroups->all();
                            }

                            return collect($area['permissions'] ?? [])->pluck('name')->all();
                        }))
                    ->all();
            })
            ->unique()
            ->values()
            ->all();

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'document_number' => $user->document_number,
            'email' => 'permissions-check@example.com',
            'password' => '',
            'role' => 'usuario',
            'is_active' => '1',
            'must_change_password' => '0',
            'permissions' => $permissionNames,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.users.edit', $user));
    }
}
