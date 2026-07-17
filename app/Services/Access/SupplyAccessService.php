<?php

namespace App\Services\Access;

use App\Models\User;

class SupplyAccessService
{
    /** @var array<int, string> */
    public const BASE_AREA_TABS = ['my_requests'];

    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function hasBoardVisibility(User $user, string $module): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can("view.board.{$module}.suministros");
    }

    public function baseAreaBoardVisible(User $user, string $areaKey): bool
    {
        return $user->hasAssignedArea()
            && $user->area_key === $areaKey
            && $user->can('supply.tab.my_requests');
    }

    public function canViewSupplyBoard(User $user, string $areaKey): bool
    {
        if ($this->hasBoardVisibility($user, $areaKey)) {
            return true;
        }

        return $this->baseAreaBoardVisible($user, $areaKey);
    }

    public function canAccessTab(User $user, string $module, string $tab): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if ($tab === 'my_requests') {
            return $this->canAccessBaseAreaTab($user, $module);
        }

        if (! $this->hasBoardVisibility($user, $module)) {
            return false;
        }

        return match ($tab) {
            'quality' => $user->can('supply.tab.quality') || $user->can('approve.supply.quality'),
            'catalog' => $user->can('supply.tab.catalog') || $user->can('manage.supply.catalog'),
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user, string $moduleKey): array
    {
        $tabs = [];

        if ($this->canAccessTab($user, $moduleKey, 'my_requests')) {
            $tabs[] = 'mis_solicitudes';
        }

        if ($this->canAccessTab($user, $moduleKey, 'quality')) {
            $tabs[] = 'aprobacion_insumos';
            $tabs[] = 'insumos_aprobados';
        }

        if ($this->canAccessTab($user, $moduleKey, 'catalog')) {
            $tabs[] = 'catalogo';
        }

        return array_values(array_unique($tabs));
    }

    private function canAccessBaseAreaTab(User $user, string $module): bool
    {
        if (! $user->hasAssignedArea() || $user->area_key !== $module) {
            return false;
        }

        return $user->can('supply.tab.my_requests');
    }
}
