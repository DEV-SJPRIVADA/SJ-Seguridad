<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PurchaseAccessService
{
    /** @var array<int, string> */
    public const BASE_AREA_TABS = ['create', 'my_requests'];

    public function isAdminBypass(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->can('manage.users');
    }

    public function hasBoardVisibility(User $user, string $module): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can("view.board.{$module}.solicitudes_compra")
            || $user->can("view.board.{$module}.bandeja_compras");
    }

    public function baseAreaBoardVisible(User $user, string $areaKey): bool
    {
        return $user->hasAssignedArea()
            && $user->area_key === $areaKey
            && ($user->can('purchase.tab.create') || $user->can('purchase.tab.my_requests'));
    }

    public function canViewPurchaseBoard(User $user, string $areaKey): bool
    {
        if ($areaKey === 'compras' && (
            $user->can('purchase.tab.approval') || $user->can('purchase.tab.processing')
        )) {
            return true;
        }

        if ($this->hasBoardVisibility($user, $areaKey)) {
            return true;
        }

        return $this->baseAreaBoardVisible($user, $areaKey);
    }

    public function bandejaAccessibleViaPurchaseBoard(User $user, string $areaKey): bool
    {
        return $user->can('purchase.tab.processing')
            && $this->canViewPurchaseBoard($user, $areaKey);
    }

    public function canAccessTab(User $user, string $module, string $tab): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if ($tab === 'providers') {
            return $this->canViewProviders($user);
        }

        if ($tab === 'providers.edit') {
            return $this->canEditProviders($user);
        }

        if ($tab === 'approval') {
            return $user->can('purchase.tab.approval');
        }

        if ($tab === 'processing') {
            return $user->can('purchase.tab.processing');
        }

        if (in_array($tab, ['create', 'my_requests'], true)) {
            return $this->canAccessSolicitudTab($user, $module, $tab);
        }

        return false;
    }

    /**
     * El permiso de edición implica ver el catálogo de proveedores.
     */
    public function canViewProviders(User $user): bool
    {
        return $this->canEditProviders($user) || $user->can('purchase.providers.view');
    }

    public function canEditProviders(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('purchase.providers.edit');
    }

    private function canAccessSolicitudTab(User $user, string $module, string $tab): bool
    {
        return match ($tab) {
            'create' => $user->can('purchase.tab.create'),
            'my_requests' => $user->can('purchase.tab.my_requests'),
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user, string $moduleKey): array
    {
        $tabs = [];

        if ($this->canAccessTab($user, $moduleKey, 'create')) {
            $tabs[] = 'nueva';
        }

        if ($this->canAccessTab($user, $moduleKey, 'my_requests')) {
            $tabs[] = 'mis_solicitudes';
        }

        if ($this->canAccessTab($user, $moduleKey, 'approval')) {
            $tabs[] = 'pendientes_aprobacion';
        }

        if ($this->canAccessTab($user, $moduleKey, 'processing')) {
            $tabs[] = 'bandeja_compras';
        }

        return $tabs;
    }

    /**
     * El catálogo de Proveedores es un tablero del área Compras, no una pestaña
     * de Solicitudes de compra. Visible con `purchase.providers.view` (edit implica view).
     */
    public function canViewProvidersBoard(User $user, string $areaKey): bool
    {
        return $areaKey === 'compras' && $this->canViewProviders($user);
    }

    /**
     * Las órdenes de compra (FO-AD-07) son un tablero del área Compras.
     * Visible con el mismo permiso que la bandeja de procesamiento.
     */
    public function canViewOrdersBoard(User $user, string $areaKey): bool
    {
        return $areaKey === 'compras' && $this->canEmitOrder($user);
    }

    /**
     * Autoriza la emisión de una Orden de Compra (FO-AD-07).
     *
     * Permiso: purchase.tab.processing (o bypass admin). No requiere permiso nuevo.
     */
    public function canEmitOrder(User $user): bool
    {
        return $this->isAdminBypass($user) || $user->can('purchase.tab.processing');
    }

    /**
     * @return Builder<User>
     */
    public function approversQuery(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'director'))
            ->permission('purchase.tab.approval')
            ->orderBy('name');
    }
}
