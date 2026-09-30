<?php

namespace App\Services\Access;

use App\Models\User;

class ReportesNovedadesAccessService
{
    public const SHEETS = [
        'vacaciones',
        'incapacidades',
        'retiros',
        'permisos',
    ];

    public function isAdminBypass(User $user): bool
    {
        return $user->can('manage.users');
    }

    public function canViewReportesNovedadesBoard(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if ($user->can('view.board.gestion_humana.reportes_novedades')) {
            return true;
        }

        return $this->hasAnySheetAccess($user);
    }

    public function hasAnySheetAccess(User $user): bool
    {
        foreach (self::SHEETS as $sheet) {
            if ($this->canAccessSheet($user, $sheet)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessSheet(User $user, string $sheet): bool
    {
        return $this->canView($user, $sheet)
            || $this->canEdit($user, $sheet)
            || $this->canReview($user, $sheet);
    }

    public function canView(User $user, string $sheet): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if (! $this->isValidSheet($sheet)) {
            return false;
        }

        return $user->can("reportes_novedades.{$sheet}.view")
            || $user->can("reportes_novedades.{$sheet}.edit")
            || $user->can("reportes_novedades.{$sheet}.review");
    }

    public function canEdit(User $user, string $sheet): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if (! $this->isValidSheet($sheet)) {
            return false;
        }

        return $user->can("reportes_novedades.{$sheet}.edit");
    }

    public function canReview(User $user, string $sheet): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        if (! $this->isValidSheet($sheet)) {
            return false;
        }

        return $user->can("reportes_novedades.{$sheet}.review");
    }

    public function canExport(User $user, string $sheet): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        return $this->canView($user, $sheet);
    }

    public function canEditAnySheet(User $user): bool
    {
        if ($this->isAdminBypass($user)) {
            return true;
        }

        foreach (self::SHEETS as $sheet) {
            if ($this->canEdit($user, $sheet)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessTab(User $user, string $tab): bool
    {
        return $this->canAccessSheet($user, $tab);
    }

    /**
     * @return array<int, string>
     */
    public function visibleTabsFor(User $user): array
    {
        $tabs = [];

        foreach (array_keys(config('access.reportes_novedades_tabs', [])) as $tab) {
            if ($this->canAccessTab($user, $tab)) {
                $tabs[] = $tab;
            }
        }

        return $tabs;
    }

    private function isValidSheet(string $sheet): bool
    {
        return in_array($sheet, self::SHEETS, true);
    }
}
