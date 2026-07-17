<?php

namespace App\Services\Admin;

class UserPermissionValidator
{
    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public function warnings(?string $areaKey, array $permissions): array
    {
        $warnings = [];
        $permissionSet = collect($permissions);

        if ($permissionSet->intersect(['requisitions.tab.solicitar', 'requisitions.tab.seguimiento'])->isNotEmpty() && blank($areaKey)) {
            $warnings[] = 'Marco Solicitar o Mis requisiciones pero no definio area base. Esas acciones no funcionaran.';
        }

        if ($permissionSet->contains('supply.tab.my_requests') && blank($areaKey)) {
            $warnings[] = 'Marco Mis solicitudes de suministros pero no definio area base.';
        }

        $requisitionScoped = $permissionSet->intersect([
            'requisitions.tab.gestion',
            'requisitions.tab.dashboard',
            'manage.requisition.parameters',
        ]);

        if ($requisitionScoped->isNotEmpty()) {
            $hasRequisitionBoard = $permissionSet->contains(fn (string $name) => str_starts_with($name, 'view.board.') && str_ends_with($name, '.requisiciones'));

            if (! $hasRequisitionBoard) {
                $warnings[] = 'Marco acciones de Requisiciones por tablero visible, pero no habilito ver Requisiciones en el menu de ninguna area.';
            }
        }

        $supplyScoped = $permissionSet->intersect([
            'supply.tab.quality',
            'supply.tab.catalog',
            'approve.supply.quality',
            'manage.supply.catalog',
        ]);

        if ($supplyScoped->isNotEmpty()) {
            $hasSupplyBoard = $permissionSet->contains(fn (string $name) => str_starts_with($name, 'view.board.') && str_ends_with($name, '.suministros'));

            if (! $hasSupplyBoard) {
                $warnings[] = 'Marco acciones de Suministros por tablero visible, pero no habilito ver Suministros en el menu de ninguna area.';
            }
        }

        return array_values(array_unique($warnings));
    }
}
