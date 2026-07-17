<?php

namespace App\Services\Access;

use App\Models\User;

class RequisitionAccessService
{
    /** @var array<int, string> */
    public const BASE_AREA_TABS = ['solicitar', 'seguimiento'];

    /** @var array<string, string> */
    private const TAB_PERMISSIONS = [
        'dashboard' => 'requisitions.tab.dashboard',
        'solicitar' => 'requisitions.tab.solicitar',
        'seguimiento' => 'requisitions.tab.seguimiento',
        'gestion' => 'requisitions.tab.gestion',
        'parametros' => 'manage.requisition.parameters',
    ];

    public function isBaseAreaTab(string $tab): bool
    {
        return in_array($tab, self::BASE_AREA_TABS, true);
    }

    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function isHrOperator(User $user): bool
    {
        return $user->can('manage.requisitions') || $this->isAdminBypass($user);
    }

    public function hasBaseAreaRequisitionAccess(User $user): bool
    {
        return $user->can('requisitions.tab.solicitar')
            || $user->can('requisitions.tab.seguimiento');
    }

    public function hasBoardVisibility(User $user, string $module): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can("view.board.{$module}.requisiciones");
    }

    public function canViewRequisitionsBoard(User $user, string $areaKey): bool
    {
        if ($this->hasBoardVisibility($user, $areaKey)) {
            return true;
        }

        return $this->baseAreaBoardVisible($user, $areaKey);
    }

    public function baseAreaBoardVisible(User $user, string $areaKey): bool
    {
        return $user->hasAssignedArea()
            && $user->area_key === $areaKey
            && $this->hasBaseAreaRequisitionAccess($user);
    }

    public function areaVisibleInSidebar(User $user, string $areaKey): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        foreach (array_keys(config('access.boards', [])) as $boardKey) {
            if (in_array($boardKey, ['documentos', 'indicadores', 'matriz_clientes', 'servicios_comerciales'], true)) {
                continue;
            }

            if ($user->can("view.board.{$areaKey}.{$boardKey}")) {
                return true;
            }
        }

        if ($this->baseAreaBoardVisible($user, $areaKey)) {
            return true;
        }

        if ($user->hasAssignedArea()
            && $user->area_key === $areaKey
            && $user->can('supply.tab.my_requests')) {
            return true;
        }

        return false;
    }

    public function canAccessTab(User $user, string $module, string $tab): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if ($this->isBaseAreaTab($tab)) {
            return $this->canAccessBaseAreaTab($user, $module, $tab);
        }

        return $this->canAccessModuleScopedTab($user, $module, $tab);
    }

    public function canAccessRequisitionRecord(User $user, string $module, string $requestingAreaKey): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if ($this->canAccessTab($user, $module, 'gestion') || $this->isHrOperator($user)) {
            return true;
        }

        return $requestingAreaKey === $module;
    }

    public function usesGlobalManagementScope(User $user, string $module): bool
    {
        return $this->canAccessTab($user, $module, 'gestion') || $this->isHrOperator($user);
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user, string $moduleKey): array
    {
        $tabs = [];

        foreach (array_keys(self::TAB_PERMISSIONS) as $tab) {
            if ($this->canAccessTab($user, $moduleKey, $tab)) {
                $tabs[] = $tab;
            }
        }

        return $tabs;
    }

    private function canAccessBaseAreaTab(User $user, string $module, string $tab): bool
    {
        if (! $user->hasAssignedArea() || $user->area_key !== $module) {
            return false;
        }

        $permission = self::TAB_PERMISSIONS[$tab] ?? null;

        return $permission !== null && $user->can($permission);
    }

    private function canAccessModuleScopedTab(User $user, string $module, string $tab): bool
    {
        if (! $this->hasBoardVisibility($user, $module)) {
            return false;
        }

        $permission = self::TAB_PERMISSIONS[$tab] ?? null;

        if ($permission === null) {
            return false;
        }

        if ($tab === 'gestion') {
            return $user->can($permission) || $user->can('manage.requisitions');
        }

        return $user->can($permission);
    }
}
