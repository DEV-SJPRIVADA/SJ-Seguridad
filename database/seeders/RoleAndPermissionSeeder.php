<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(config('access.system_permissions'))
            ->keys()
            ->merge(
                collect(config('access.areas'))->flatMap(fn (string $label, string $key) => [
                    "view.area.{$key}",
                    "manage.area.{$key}",
                ])
            )
            ->merge(
                collect(config('access.areas'))->keys()->flatMap(fn (string $areaKey) => (
                    collect(config('access.boards'))->keys()->map(fn (string $boardKey) => "view.board.{$areaKey}.{$boardKey}")
                ))
            )
            ->unique()
            ->values();

        $permissions->each(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        Permission::query()
            ->where(function ($query): void {
                $query->where('name', 'like', 'view.area.%')
                    ->orWhere('name', 'like', 'manage.area.%')
                    ->orWhere('name', 'like', 'view.board.%');
            })
            ->whereNotIn('name', $permissions->all())
            ->get()
            ->each
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::query()->pluck('name')->all();
        $roles = [
            'super-admin' => $allPermissions,
            'administrador' => ['view.dashboard', 'manage.users', 'manage.requisition.parameters'],
            'usuario' => ['view.dashboard'],
        ];

        foreach ($roles as $name => $rolePermissions) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($rolePermissions);
        }

        foreach (['consulta', 'coordinador'] as $legacyRoleName) {
            $legacyRole = Role::query()->where('name', $legacyRoleName)->first();

            if (! $legacyRole) {
                continue;
            }

            User::role($legacyRoleName)->get()->each(function (User $user) use ($legacyRoleName): void {
                $user->removeRole($legacyRoleName);

                if ($user->roles()->count() === 0) {
                    $user->assignRole('usuario');
                }
            });

            $legacyRole->delete();
        }

        $admin = User::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@sjseguridad.local')],
            [
                'name' => env('ADMIN_NAME', 'Administrador SJ Seguridad'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe123!')),
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]
        );

        $admin->forceFill([
            'name' => env('ADMIN_NAME', $admin->name),
            'is_active' => true,
            'must_change_password' => false,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();

        $admin->syncRoles(['super-admin']);
        $admin->syncPermissions([]);
    }
}
