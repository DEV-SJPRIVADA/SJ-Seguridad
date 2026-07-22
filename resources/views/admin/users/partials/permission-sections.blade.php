@props(['sections', 'selectedPermissions', 'help'])

@php
    $assigned = $sections['assigned_area'] ?? [];
    $global = $sections['global'] ?? [];
    $otherAreas = $sections['other_areas'] ?? [];

    $countSelected = fn (array $permissions) => collect($permissions)
        ->filter(fn (array $perm) => in_array($perm['name'], $selectedPermissions, true))
        ->count();

    $assignedPermissions = $assigned['permissions'] ?? [];
    $assignedSelected = $countSelected($assignedPermissions);

    $globalPermissions = collect($global['groups'] ?? [])
        ->flatMap(fn (array $group) => $group['permissions'] ?? [])
        ->values()
        ->all();
    $globalSelected = $countSelected($globalPermissions);

    $otherPermissions = collect($otherAreas['areas'] ?? [])
        ->flatMap(fn (array $area) => $area['permissions'] ?? [])
        ->values()
        ->all();
    $otherSelected = $countSelected($otherPermissions);

    $otherGroups = collect($otherAreas['areas'] ?? [])
        ->flatMap(function (array $area): array {
            $subgroups = $area['subgroups'] ?? [];

            if ($subgroups !== []) {
                return collect($subgroups)
                    ->map(fn (array $subgroup) => [
                        'label' => ($area['label'] ?? '').' — '.($subgroup['label'] ?? ''),
                        'permissions' => $subgroup['permissions'] ?? [],
                    ])
                    ->all();
            }

            return [[
                'label' => $area['label'] ?? '',
                'permissions' => $area['permissions'] ?? [],
            ]];
        })
        ->all();
@endphp

<div class="perm-accordion permission-sections">
    <x-permission-accordion
        id="assigned"
        icon="📍"
        :title="$assigned['label'] ?? 'En su area asignada'"
        :help="$assigned['help'] ?? ''"
        meta='Area base: <strong id="assigned-area-label">Sin area fija</strong>'
        :open="true"
        :search="'area asignada '.Str::lower($assigned['label'] ?? '')"
        :badge="$assignedSelected"
        :total="count($assignedPermissions)"
    >
        @include('admin.users.partials.permission-modules.switch-list', [
            'permissions' => $assignedPermissions,
            'selectedPermissions' => $selectedPermissions,
        ])
    </x-permission-accordion>

    <x-permission-accordion
        id="global"
        icon="🌐"
        :title="$global['label'] ?? 'Funcionalidades transversales'"
        :help="$global['help'] ?? ''"
        :open="false"
        :search="'transversales global '.Str::lower($global['label'] ?? '')"
        :badge="$globalSelected"
        :total="count($globalPermissions)"
    >
        @include('admin.users.partials.permission-modules.subgroup-list', [
            'groups' => $global['groups'] ?? [],
            'selectedPermissions' => $selectedPermissions,
            'firstOpen' => true,
        ])
    </x-permission-accordion>

    <x-permission-accordion
        id="other"
        icon="🗂️"
        :title="$otherAreas['label'] ?? 'Activa visualizacion de otras areas'"
        :help="$otherAreas['help'] ?? ''"
        :open="false"
        :search="'otras areas '.Str::lower($otherAreas['label'] ?? '')"
        :badge="$otherSelected"
        :total="count($otherPermissions)"
    >
        @include('admin.users.partials.permission-modules.subgroup-list', [
            'groups' => $otherGroups,
            'selectedPermissions' => $selectedPermissions,
            'firstOpen' => false,
            'areaStyle' => true,
        ])
    </x-permission-accordion>
</div>
