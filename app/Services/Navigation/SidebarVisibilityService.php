<?php

namespace App\Services\Navigation;

use App\Models\User;
use App\Services\Access\ArchivoAccessService;
use App\Services\Access\BoardAccessService;
use App\Services\Access\CommercialAccessService;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\Access\PurchaseAccessService;
use App\Services\Access\RequisitionAccessService;
use App\Services\Access\SupplyAccessService;

class SidebarVisibilityService
{
    public function __construct(
        private readonly RequisitionAccessService $requisitionAccess,
        private readonly SupplyAccessService $supplyAccess,
        private readonly BoardAccessService $boardAccess,
        private readonly CommercialAccessService $commercialAccess,
        private readonly FichaEmpleadosAccessService $fichaEmpleadosAccess,
        private readonly ArchivoAccessService $archivoAccess,
        private readonly PurchaseAccessService $purchaseAccess,
    ) {}

    public function shouldShowArea(User $user, string $areaKey): bool
    {
        foreach (array_keys(config('access.boards', [])) as $boardKey) {
            if ($this->shouldShowBoard($user, $areaKey, $boardKey)) {
                return true;
            }
        }

        return false;
    }

    public function shouldShowBoard(User $user, string $areaKey, string $boardKey): bool
    {
        return match ($boardKey) {
            'requisiciones' => $this->shouldShowRequisitionsBoard($user, $areaKey),
            'suministros' => $this->shouldShowSupplyBoard($user, $areaKey),
            'solicitudes_compra' => $this->shouldShowPurchaseBoard($user, $areaKey),
            'bandeja_compras' => $this->shouldShowBandejaComprasBoard($user, $areaKey),
            'documentos' => $this->shouldShowDocumentsBoard($user, $areaKey),
            'ficha_empleados' => $this->shouldShowFichaEmpleadosBoard($user, $areaKey),
            'archivo' => $this->shouldShowArchivoBoard($user, $areaKey),
            'indicadores' => $this->shouldShowIndicadoresBoard($user, $areaKey),
            'gestion_clientes' => $this->shouldShowGestionClientesBoard($user, $areaKey),
            'dashboard' => $this->shouldShowDashboardBoard($user, $areaKey),
            default => $user->can("view.board.{$areaKey}.{$boardKey}"),
        };
    }

    private function shouldShowRequisitionsBoard(User $user, string $areaKey): bool
    {
        if ($this->requisitionAccess->baseAreaBoardVisible($user, $areaKey)) {
            return true;
        }

        if ($areaKey === 'gestion_humana' && $this->hasGlobalGhRequisitionScope($user)) {
            return true;
        }

        if ($this->hasGlobalGhRequisitionScope($user)) {
            return false;
        }

        return $user->can("view.board.{$areaKey}.requisiciones");
    }

    private function shouldShowSupplyBoard(User $user, string $areaKey): bool
    {
        if ($this->supplyAccess->baseAreaBoardVisible($user, $areaKey)) {
            return true;
        }

        if ($areaKey === 'compras' && $this->hasComprasSupplyScope($user)) {
            return true;
        }

        if ($areaKey === 'calidad' && $this->hasCalidadSupplyScope($user)) {
            return true;
        }

        if ($this->hasComprasSupplyScope($user) || $this->hasCalidadSupplyScope($user)) {
            return false;
        }

        return $user->can("view.board.{$areaKey}.suministros");
    }

    private function shouldShowPurchaseBoard(User $user, string $areaKey): bool
    {
        if ($this->purchaseAccess->baseAreaBoardVisible($user, $areaKey)) {
            return true;
        }

        if ($areaKey === 'compras' && $this->hasComprasPurchaseScope($user)) {
            return true;
        }

        if ($this->hasComprasPurchaseScope($user)) {
            return false;
        }

        return $user->can("view.board.{$areaKey}.solicitudes_compra");
    }

    private function shouldShowBandejaComprasBoard(User $user, string $areaKey): bool
    {
        if ($areaKey !== 'compras') {
            return false;
        }

        if ($this->purchaseAccess->bandejaAccessibleViaPurchaseBoard($user, $areaKey)) {
            return false;
        }

        return $user->can('purchase.tab.processing')
            || $user->can('view.board.compras.bandeja_compras');
    }

    private function shouldShowDocumentsBoard(User $user, string $areaKey): bool
    {
        return $this->boardAccess->canViewDocumentsBoardForSidebar($user, $areaKey);
    }

    private function shouldShowFichaEmpleadosBoard(User $user, string $areaKey): bool
    {
        if ($areaKey !== 'gestion_humana') {
            return false;
        }

        return $this->fichaEmpleadosAccess->canViewFichaEmpleadosBoard($user);
    }

    private function shouldShowArchivoBoard(User $user, string $areaKey): bool
    {
        if ($areaKey !== 'gestion_humana') {
            return false;
        }

        return $this->archivoAccess->canViewArchivoBoard($user);
    }

    private function shouldShowIndicadoresBoard(User $user, string $areaKey): bool
    {
        if ($areaKey !== 'operaciones') {
            return false;
        }

        return $user->can('operations.view')
            || $user->can('operations.manage')
            || $user->can('operations.capture');
    }

    private function shouldShowGestionClientesBoard(User $user, string $areaKey): bool
    {
        if ($areaKey !== 'comercial') {
            return false;
        }

        return $this->commercialAccess->canViewGestionClientesBoard($user);
    }

    private function shouldShowDashboardBoard(User $user, string $areaKey): bool
    {
        if ($areaKey === 'comercial') {
            return $user->can('comercial.matriz.view')
                || $user->can('comercial.matriz.manage')
                || $user->can('view.board.comercial.dashboard')
                || $user->can('view.area.comercial');
        }

        if ($areaKey === 'compras') {
            return $user->can('view.board.compras.dashboard')
                || $user->can('purchase.tab.processing')
                || $user->can('view.area.compras')
                || $user->can('manage.area.compras');
        }

        if ($user->can('manage.users')) {
            return in_array($areaKey, $this->compactNavigationAreas($user), true);
        }

        return $user->can("view.board.{$areaKey}.dashboard");
    }

    private function hasGlobalGhRequisitionScope(User $user): bool
    {
        return $user->can('requisitions.tab.gestion')
            || $user->can('requisitions.tab.dashboard')
            || $user->can('manage.requisition.parameters')
            || $user->can('requisitions.approve.management')
            || $user->can('manage.users');
    }

    private function hasComprasSupplyScope(User $user): bool
    {
        return $user->can('supply.tab.catalog')
            || $user->can('manage.supply.catalog')
            || $user->can('purchase.tab.processing')
            || $user->can('manage.users');
    }

    private function hasCalidadSupplyScope(User $user): bool
    {
        return $user->can('supply.tab.quality')
            || $user->can('approve.supply.quality');
    }

    private function hasComprasPurchaseScope(User $user): bool
    {
        return $user->can('purchase.tab.approval')
            || $user->can('purchase.tab.processing')
            || $user->can('manage.users');
    }

    /**
     * @return array<int, string>
     */
    private function compactNavigationAreas(User $user): array
    {
        $areas = [
            'gestion_humana',
            'compras',
            'calidad',
            'operaciones',
            'comercial',
        ];

        if ($user->hasAssignedArea() && ! in_array($user->area_key, $areas, true)) {
            $areas[] = $user->area_key;
        }

        return $areas;
    }
}
