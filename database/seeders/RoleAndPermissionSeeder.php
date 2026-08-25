<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PermissionCatalog::sync();

        $allPermissions = Permission::query()->pluck('name')->all();
        $roles = [
            'super-admin' => $allPermissions,
            'administrador' => [
                'view.dashboard',
                'manage.requisition.parameters',
                'requisitions.approve.management',
                'view.board.gestion_humana.requisiciones',
                'view.board.gestion_humana.plantillas_word',
                'plantillas_word.view',
                'plantillas_word.manage',
            ],
            'director' => [
                'view.dashboard',
                'purchase.tab.approval',
                'view.board.compras.solicitudes_compra',
            ],
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
                'document_number' => env('ADMIN_DOCUMENT_NUMBER', '9000000001'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe123!')),
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]
        );

        $admin->forceFill([
            'name' => env('ADMIN_NAME', $admin->name),
            'document_number' => $admin->document_number ?: env('ADMIN_DOCUMENT_NUMBER', '9000000001'),
            'is_active' => true,
            'must_change_password' => false,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();

        $admin->syncRoles(['super-admin']);
        $admin->syncPermissions([]);
    }
}
