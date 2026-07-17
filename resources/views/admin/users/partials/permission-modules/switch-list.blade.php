<div class="switch-group switch-group--compact">
    @foreach ($permissions as $perm)
        @include('admin.users.partials.permission-modules.switch-item', [
            'perm' => $perm,
            'selectedPermissions' => $selectedPermissions,
        ])
    @endforeach
</div>
