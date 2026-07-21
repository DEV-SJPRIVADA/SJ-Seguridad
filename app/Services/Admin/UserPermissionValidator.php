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
            $hasRequisitionBoard = $permissionSet->contains(
                fn (string $name) => str_starts_with($name, 'view.board.') && str_ends_with($name, '.requisiciones')
            );

            if (! $hasRequisitionBoard) {
                $warnings[] = 'Marco acciones de Requisiciones (GH), pero no habilito ver Requisiciones en el menu de ninguna area.';
            }
        }

        $supplyQualityScoped = $permissionSet->intersect([
            'supply.tab.quality',
            'approve.supply.quality',
        ]);

        if ($supplyQualityScoped->isNotEmpty() && ! $this->hasSupplyBoard($permissionSet)) {
            $warnings[] = 'Marco acciones de Suministros para Calidad, pero no habilito ver Suministros en el menu de ninguna area (normalmente Compras).';
        }

        $supplyPurchasingScoped = $permissionSet->intersect([
            'supply.tab.catalog',
            'manage.supply.catalog',
        ]);

        if ($supplyPurchasingScoped->isNotEmpty()) {
            if (! $this->hasSupplyBoard($permissionSet)) {
                $warnings[] = 'Marco acciones de catalogo de Suministros (Compras), pero no habilito ver Suministros en el menu de ninguna area.';
            }

            if (! $permissionSet->contains('view.board.compras.suministros')) {
                $warnings[] = 'Las acciones de catalogo de Suministros suelen combinarse con el tablero Suministros en el area Compras.';
            }
        }

        $operationsScoped = $permissionSet->intersect([
            'operations.view',
            'operations.capture',
            'operations.manage',
            'operations.export',
        ]);

        if ($operationsScoped->isNotEmpty()) {
            $hasOperationsBoard = $permissionSet->contains(
                fn (string $name) => str_starts_with($name, 'view.board.operaciones.')
            );

            if (! $hasOperationsBoard && $areaKey !== 'operaciones') {
                $warnings[] = 'Marco permisos de Indicadores, pero no tiene tableros visibles en Operaciones ni area base Operaciones.';
            }
        }

        $commercialScoped = $permissionSet->intersect([
            'comercial.matriz.view',
            'comercial.matriz.manage',
        ]);

        if ($commercialScoped->isNotEmpty()) {
            $hasCommercialBoard = $permissionSet->contains(
                fn (string $name) => str_starts_with($name, 'view.board.comercial.')
            );

            if (! $hasCommercialBoard) {
                $warnings[] = 'Marco funciones de Matriz comercial, pero no habilito tableros visibles en Comercial.';
            }
        }

        if ($permissionSet->contains('manage.quality.documents')
            && ! $permissionSet->contains('view.area.calidad')
            && ! $permissionSet->contains('manage.area.calidad')
        ) {
            $warnings[] = 'Marco administrar documentos de Calidad, pero no habilito acceso al area Calidad (biblioteca o gestion).';
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $permissionSet
     */
    private function hasSupplyBoard(\Illuminate\Support\Collection $permissionSet): bool
    {
        return $permissionSet->contains(
            fn (string $name) => str_starts_with($name, 'view.board.') && str_ends_with($name, '.suministros')
        );
    }
}
