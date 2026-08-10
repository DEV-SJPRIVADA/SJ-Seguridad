<div class="perm-subgroups">
    @foreach ($groups as $group)
        <div
            class="perm-subgroup perm-subgroup--expanded {{ ! empty($areaStyle) ? 'perm-subgroup--area js-permission-area' : 'js-permission-group' }}"
            data-search="{{ Str::lower($group['label']) }}"
        >
            <div class="perm-subgroup__header">
                <span class="perm-subgroup__title">{{ $group['label'] }}</span>
                <span class="perm-subgroup__count js-perm-subgroup-count"></span>
            </div>
            @include('admin.users.partials.permission-modules.switch-list', [
                'permissions' => $group['permissions'],
                'selectedPermissions' => $selectedPermissions,
            ])
        </div>
    @endforeach
</div>
