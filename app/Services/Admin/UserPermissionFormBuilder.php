<?php

namespace App\Services\Admin;

use App\Support\PermissionCatalog;
use Spatie\Permission\Models\Permission;

class UserPermissionFormBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        PermissionCatalog::ensureSystemPermissions();

        $allPermissions = Permission::query()->pluck('name')->all();
        $hiddenFromAdmin = config('access.admin_hidden_permissions', []);
        $systemLabels = config('access.system_permissions', []);
        $areas = config('access.areas', []);
        $indicadorLabels = config('access.area_indicador_permissions', []);
        $sectionLabels = config('access.admin_ui.sections', []);

        $labelFor = function (string $name) use ($systemLabels, $indicadorLabels): string {
            foreach ($indicadorLabels as $group) {
                if (isset($group[$name])) {
                    return $group[$name];
                }
            }

            if (isset($systemLabels[$name])) {
                return $systemLabels[$name];
            }

            if (preg_match('/^view\.board\.(.+)\.(.+)$/', $name, $matches) === 1) {
                $areaLabel = config("access.areas.{$matches[1]}", $matches[1]);
                $boardLabel = config("access.boards.{$matches[2]}", $matches[2]);

                return "Ver {$boardLabel} ({$areaLabel})";
            }

            if (preg_match('/^view\.area\.(.+)$/', $name, $matches) === 1) {
                $areaLabel = config("access.areas.{$matches[1]}", $matches[1]);

                return "Biblioteca en {$areaLabel}";
            }

            if (preg_match('/^manage\.area\.(.+)$/', $name, $matches) === 1) {
                $areaLabel = config("access.areas.{$matches[1]}", $matches[1]);

                return "Gestionar area {$areaLabel}";
            }

            return $name;
        };

        $assignedArea = collect(config('access.admin_ui.assigned_area_permissions', []))
            ->map(fn (string $name) => $this->permissionItem($name, $labelFor($name), $allPermissions, $hiddenFromAdmin))
            ->filter()
            ->values()
            ->all();

        $globalGroups = collect(config('access.admin_ui.global_groups', []))
            ->map(function (array $group, string $key) use ($allPermissions, $hiddenFromAdmin, $areas, $labelFor): ?array {
                $permissions = collect($group['permissions'] ?? [])
                    ->map(fn (string $name) => $this->permissionItem($name, $labelFor($name), $allPermissions, $hiddenFromAdmin))
                    ->filter()
                    ->values();

                if (! empty($group['view_area_access'])) {
                    foreach ($areas as $areaKey => $areaLabel) {
                        if ($areaKey === 'calidad') {
                            continue;
                        }

                        $name = "view.area.{$areaKey}";
                        if (! in_array($name, $allPermissions, true)) {
                            continue;
                        }

                        $permissions->push([
                            'name' => $name,
                            'label' => "Biblioteca en {$areaLabel}",
                            'help' => $name,
                        ]);
                    }
                }

                if ($permissions->isEmpty()) {
                    return null;
                }

                return [
                    'key' => $key,
                    'label' => $group['label'],
                    'permissions' => $permissions->values()->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $otherAreas = collect(config('access.admin_ui.other_areas', []))
            ->map(function (array $areaConfig, string $areaKey) use ($allPermissions, $hiddenFromAdmin, $labelFor): ?array {
                $areaLabel = $areaConfig['label'] ?? config("access.areas.{$areaKey}", $areaKey);

                $subgroups = collect($areaConfig['subgroups'] ?? [])
                    ->map(function (array $subgroup, string $subgroupKey) use ($allPermissions, $hiddenFromAdmin, $labelFor): ?array {
                        $permissions = collect($subgroup['permissions'] ?? [])
                            ->map(fn (string $name) => $this->permissionItem($name, $labelFor($name), $allPermissions, $hiddenFromAdmin))
                            ->filter()
                            ->values()
                            ->all();

                        if ($permissions === []) {
                            return null;
                        }

                        return [
                            'key' => $subgroupKey,
                            'label' => $subgroup['label'] ?? $subgroupKey,
                            'permissions' => $permissions,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                if ($subgroups === [] && ! empty($areaConfig['permissions'])) {
                    $permissions = collect($areaConfig['permissions'])
                        ->map(fn (string $name) => $this->permissionItem($name, $labelFor($name), $allPermissions, $hiddenFromAdmin))
                        ->filter()
                        ->values()
                        ->all();

                    if ($permissions === []) {
                        return null;
                    }

                    $subgroups = [[
                        'key' => 'default',
                        'label' => 'Permisos',
                        'permissions' => $permissions,
                    ]];
                }

                if ($subgroups === []) {
                    return null;
                }

                $flatPermissions = collect($subgroups)
                    ->flatMap(fn (array $subgroup) => $subgroup['permissions'])
                    ->values()
                    ->all();

                return [
                    'key' => $areaKey,
                    'label' => $areaLabel,
                    'subgroups' => $subgroups,
                    'permissions' => $flatPermissions,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'sections' => [
                'assigned_area' => [
                    'label' => $sectionLabels['assigned_area'] ?? 'En su area asignada',
                    'help' => config('access.admin_ui.help.assigned_area'),
                    'permissions' => $assignedArea,
                ],
                'global' => [
                    'label' => $sectionLabels['global'] ?? 'Funcionalidades transversales',
                    'help' => config('access.admin_ui.help.global'),
                    'groups' => $globalGroups,
                ],
                'other_areas' => [
                    'label' => $sectionLabels['other_areas'] ?? 'Activa visualizacion de otras areas',
                    'help' => config('access.admin_ui.help.other_areas'),
                    'areas' => $otherAreas,
                ],
            ],
            'tabs' => config('access.admin_ui.tabs', []),
            'help' => config('access.admin_ui.help', []),
        ];
    }

    /**
     * @param  array<int, string>  $allPermissions
     * @param  array<int, string>  $hiddenFromAdmin
     * @return array{name: string, label: string, help: string}|null
     */
    private function permissionItem(
        string $name,
        string $label,
        array $allPermissions,
        array $hiddenFromAdmin,
    ): ?array {
        if (! in_array($name, $allPermissions, true) || in_array($name, $hiddenFromAdmin, true)) {
            return null;
        }

        return [
            'name' => $name,
            'label' => $label,
            'help' => $name,
        ];
    }
}
