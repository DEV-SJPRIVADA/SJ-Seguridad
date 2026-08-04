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
        return $user->can('manage.users');
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

    private function canAccessSolicitudTab(User $user, string $module, string $tab): bool
    {
        $permission = match ($tab) {
            'create' => 'purchase.tab.create',
            'my_requests' => 'purchase.tab.my_requests',
            default => null,
        };

        if ($permission === null || ! $user->can($permission)) {
            return false;
        }

        if ($user->hasAssignedArea() && $user->area_key === $module) {
            return true;
        }

        $canonicalHome = config('access.board_canonical_areas.solicitudes_compra.home');
        if (is_string($canonicalHome) && $canonicalHome !== '' && $module === $canonicalHome) {
            return true;
        }

        if ($this->hasBoardVisibility($user, $module)) {
            return true;
        }

        return false;
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
