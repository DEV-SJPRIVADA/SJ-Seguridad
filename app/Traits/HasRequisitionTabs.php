<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasRequisitionTabs
{
    /**
     * @return array<int, array{key: string, label: string, url: string, active: bool}>
     */
    protected function getRequisitionSubTabs(string $module, string $activeKey): array
    {
        $user = auth()->user();

        return $user->requisitionBoardTabsFor($module)
            ->map(function (string $tabKey) use ($activeKey, $module): array {
                $routes = [
                    'dashboard' => route('requisitions.dashboard', ['module' => $module]),
                    'solicitar' => route('requisitions.create', ['module' => $module]),
                    'seguimiento' => route('requisitions.tracking', ['module' => $module]),
                    'gestion' => route('requisitions.manage', ['module' => $module]),
                    'parametros' => route('requisitions.parameters', ['module' => $module]),
                ];

                return [
                    'key' => $tabKey,
                    'label' => config("access.requisition_tabs.{$tabKey}", Str::headline($tabKey)),
                    'url' => $routes[$tabKey],
                    'active' => $tabKey === $activeKey,
                ];
            })
            ->values()
            ->all();
    }
}
