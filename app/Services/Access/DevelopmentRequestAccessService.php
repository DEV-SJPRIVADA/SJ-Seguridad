<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;

class DevelopmentRequestAccessService
{
    /** @var array<int, string> */
    public const BASE_AREA_TABS = ['create', 'my_requests'];

    public function isAdminBypass(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->can('manage.users');
    }

    public function canCreate(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('devreq.tab.create');
    }

    public function canViewOwn(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('devreq.tab.my_requests');
    }

    public function canApproveLeader(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('devreq.tab.leader_approval');
    }

    public function canProcessTic(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('devreq.tab.tic_queue');
    }

    public function canView(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('devreq.tab.view')
            || $this->canCreate($user)
            || $this->canViewOwn($user)
            || $this->canApproveLeader($user)
            || $this->canProcessTic($user);
    }

    public function hasBoardVisibility(User $user, string $module): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can("view.board.{$module}.solicitudes_desarrollo");
    }

    public function baseAreaBoardVisible(User $user, string $areaKey): bool
    {
        return $user->hasAssignedArea()
            && $user->area_key === $areaKey
            && ($this->canCreate($user) || $this->canViewOwn($user));
    }

    public function canViewDevelopmentRequestBoard(User $user, string $areaKey): bool
    {
        if ($areaKey === 'tic' && (
            $this->canApproveLeader($user) || $this->canProcessTic($user) || $user->can('devreq.tab.view')
        )) {
            return true;
        }

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

        return match ($tab) {
            'create' => $this->canCreate($user),
            'my_requests' => $this->canViewOwn($user),
            'leader_approval' => $this->canApproveLeader($user),
            'tic_queue' => $this->canProcessTic($user),
            'view' => $this->canView($user),
            default => false,
        };
    }

    /**
     * Lideres / directores que pueden aprobar radicacion.
     *
     * @return Builder<User>
     */
    public function leadersQuery(): Builder
    {
        $permission = 'devreq.tab.leader_approval';

        if (! $this->permissionExists($permission)) {
            return User::query()->whereRaw('0 = 1');
        }

        return User::query()
            ->where('is_active', true)
            ->permission($permission)
            ->orderBy('name');
    }

    private function permissionExists(string $name): bool
    {
        return Permission::query()
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->exists();
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

        if ($this->canAccessTab($user, $moduleKey, 'leader_approval')) {
            $tabs[] = 'aprobacion_lider';
        }

        if ($this->canAccessTab($user, $moduleKey, 'tic_queue')) {
            $tabs[] = 'bandeja_tic';
        }

        return $tabs;
    }
}
