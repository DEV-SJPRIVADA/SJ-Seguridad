<?php

namespace App\Services\Admin;

use Illuminate\Support\Collection;

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
            $warnings[] = 'Marcó Solicitar o Mis requisiciones pero no definió área base. Esas acciones no funcionarán.';
        }

        if ($permissionSet->contains('supply.tab.my_requests') && blank($areaKey)) {
            $warnings[] = 'Marcó Mis solicitudes de suministros pero no definió área base.';
        }

        $requisitionScoped = $permissionSet->intersect([
            'requisitions.tab.gestion',
            'requisitions.tab.dashboard',
            'manage.requisition.parameters',
        ]);

        if ($requisitionScoped->isNotEmpty()) {
            $hasGhRequisitionHome = $permissionSet->contains('view.board.gestion_humana.requisiciones')
                || $permissionSet->contains('requisitions.approve.management');

            if (! $hasGhRequisitionHome) {
                $warnings[] = 'Marcó acciones de Requisiciones (GH); el menú las muestra bajo Gestión Humana (hogar canónico). No requiere tableros en otras áreas.';
            }

            $redundantRequisitionBoards = $permissionSet->filter(
                fn (string $name): bool => str_starts_with($name, 'view.board.')
                    && str_ends_with($name, '.requisiciones')
                    && ! str_starts_with($name, 'view.board.gestion_humana.')
            );

            if ($redundantRequisitionBoards->isNotEmpty()) {
                $warnings[] = 'Tiene tableros Requisiciones fuera de Gestión Humana y alcance GH; el menú solo mostrará Requisiciones en GH (salvo su área base como solicitante).';
            }
        }

        if ($permissionSet->contains('requisitions.approve.management')
            && ! $permissionSet->contains('view.board.gestion_humana.requisiciones')
        ) {
            $warnings[] = 'Autorización gerencia aparece en el menú bajo Gestión Humana → Requisiciones (hogar canónico).';
        }

        if ($permissionSet->contains('purchase.tab.approval')) {
            $warnings[] = 'Autorización de compras aparece en Compras → Solicitudes de compra → Pendientes (hogar canónico).';
        }

        $supplyQualityScoped = $permissionSet->intersect([
            'supply.tab.quality',
            'approve.supply.quality',
        ]);

        if ($supplyQualityScoped->isNotEmpty() && ! $this->hasSupplyBoard($permissionSet)) {
            $warnings[] = 'Marcó acciones de Suministros para Calidad, pero no habilitó ver Suministros en el menú de ninguna área (normalmente Compras).';
        }

        $supplyPurchasingScoped = $permissionSet->intersect([
            'supply.tab.catalog',
            'manage.supply.catalog',
        ]);

        if ($supplyPurchasingScoped->isNotEmpty()) {
            if (! $this->hasSupplyBoard($permissionSet)) {
                $warnings[] = 'Marcó acciones de catálogo de Suministros (Compras), pero no habilitó ver Suministros en el menú de ninguna área.';
            }

            if (! $permissionSet->contains('view.board.compras.suministros')) {
                $warnings[] = 'Las acciones de catálogo de Suministros suelen combinarse con el tablero Suministros en el área Compras.';
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
                $warnings[] = 'Marcó permisos de Indicadores, pero no tiene tableros visibles en Operaciones ni área base Operaciones.';
            }
        }

        $commercialScoped = $permissionSet->intersect([
            'comercial.clients.view',
            'comercial.clients.edit',
            'comercial.services.view',
            'comercial.services.edit',
            'comercial.parameters.edit',
        ]);

        if ($commercialScoped->isNotEmpty()) {
            $hasCommercialBoard = $permissionSet->contains(
                fn (string $name) => str_starts_with($name, 'view.board.comercial.')
            );

            if (! $hasCommercialBoard) {
                $warnings[] = 'Marcó funciones de Gestión Clientes, pero no habilitó tableros visibles en Comercial.';
            }
        }

        if ($permissionSet->contains('manage.quality.documents')
            && ! $permissionSet->contains('manage.users')
            && $areaKey !== 'calidad'
        ) {
            $warnings[] = 'Marcó administrar documentos de Calidad; confirme que el usuario opera desde el área Calidad para la pestaña Administrar.';
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @param  Collection<int, string>  $permissionSet
     */
    private function hasSupplyBoard(Collection $permissionSet): bool
    {
        return $permissionSet->contains(
            fn (string $name) => str_starts_with($name, 'view.board.') && str_ends_with($name, '.suministros')
        );
    }
}
