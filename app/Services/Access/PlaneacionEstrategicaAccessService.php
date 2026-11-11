<?php

namespace App\Services\Access;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\User;
use App\Services\Calidad\Planeacion\PlaneacionSubstitutionService;

class PlaneacionEstrategicaAccessService
{
    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('view.board.calidad.planeacion_estrategica');
    }

    public function canViewDashboard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('planeacion.dashboard.view');
    }

    public function canViewIndicadores(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('planeacion.indicadores.view')
            || $user->can('planeacion.indicadores.edit');
    }

    public function canEditIndicadores(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('planeacion.indicadores.edit');
    }

    public function canViewCapture(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('planeacion.captura.view')
            || $user->can('planeacion.captura.edit');
    }

    public function canEditCapture(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $user->can('planeacion.captura.edit');
    }

    public function canConsultIndicator(User $user, QualityPlanningIndicator $indicator): bool
    {
        if ($this->canEditIndicadores($user)) {
            return true;
        }

        if (! $this->canViewIndicadores($user) || $indicator->isArchived()) {
            return false;
        }

        return $indicator->assignees()
            ->where('user_id', $user->id)
            ->where('can_consult', true)
            ->exists()
            || (
                $user->hasAssignedArea()
                && $indicator->areaAssignees()
                    ->where('area_key', $user->area_key)
                    ->where('can_consult', true)
                    ->exists()
            );
    }

    public function canCaptureIndicator(User $user, QualityPlanningIndicator $indicator): bool
    {
        if (! $this->canEditCapture($user) || $indicator->isArchived()) {
            return false;
        }

        if ($this->canEditIndicadores($user)) {
            return true;
        }

        if ($indicator->assignees()
            ->where('user_id', $user->id)
            ->where('can_capture', true)
            ->exists()) {
            return true;
        }

        if ($user->hasAssignedArea()
            && $indicator->areaAssignees()
                ->where('area_key', $user->area_key)
                ->where('can_capture', true)
                ->exists()) {
            return true;
        }

        return app(PlaneacionSubstitutionService::class)->activeFor($indicator, $user) !== null;
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        $tabs = [];

        foreach (array_keys(config('access.planeacion_tabs', [])) as $tab) {
            $allowed = match ($tab) {
                'dashboard' => $this->canViewDashboard($user),
                'indicadores' => $this->canViewIndicadores($user),
                'captura' => $this->canViewCapture($user),
                default => false,
            };

            if ($allowed) {
                $tabs[] = $tab;
            }
        }

        return $tabs;
    }
}
