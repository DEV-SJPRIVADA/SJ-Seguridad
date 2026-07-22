<div class="perm-subgroups">
    @foreach ($groups as $group)
        <details class="perm-subgroup {{ ! empty($areaStyle) ? 'perm-subgroup--area js-permission-area' : 'js-permission-group' }}" data-search="{{ Str::lower($group['label']) }}" @if ($loop->first && ($firstOpen ?? false)) open @endif>
            <summary class="perm-subgroup__summary">
                <span>{{ $group['label'] }}</span>
                <span class="perm-subgroup__count js-perm-subgroup-count"></span>
            </summary>
            @include('admin.users.partials.permission-modules.switch-list', [
                'permissions' => $group['permissions'],
                'selectedPermissions' => $selectedPermissions,
            ])
        </details>
    @endforeach
</div>
