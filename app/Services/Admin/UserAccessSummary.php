<?php

namespace App\Services\Admin;

use App\Models\User;

class UserAccessSummary
{
    /**
     * @return array{notes: array<int, string>, role_names: array<int, string>}
     */
    public function summarize(User $user): array
    {
        $notes = [];
        $roleNames = $user->roles->pluck('name')->all();

        if ($user->hasRole('super-admin')) {
            $notes[] = 'El rol super-admin otorga acceso total al sistema, aunque no haya permisos directos listados.';
        }

        if ($user->hasRole('administrador')) {
            $notes[] = 'El rol administrador incluye acceso base al panel, parámetros GH y autorización de requisiciones cargo nuevo (gerencia) en Gestión Humana → Requisiciones → Autorización gerencia.';
        }

        if ($user->hasRole('director')) {
            $notes[] = 'El rol director incluye autorización de solicitudes de compra.';
            $notes[] = 'Punto de entrada en menú: Compras → Solicitudes de compra → Pendientes autorización.';
        }

        $directCount = $user->permissions->count();
        $rolePermissionCount = $user->getPermissionsViaRoles()->count();

        if ($directCount === 0 && $rolePermissionCount > 0 && ! $user->hasRole('super-admin')) {
            $notes[] = 'Este usuario depende principalmente de los permisos heredados del rol asignado.';
        }

        return [
            'notes' => array_values(array_unique($notes)),
            'role_names' => $roleNames,
        ];
    }
}
