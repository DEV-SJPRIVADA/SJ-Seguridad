@props(['sections', 'selectedPermissions', 'help', 'initialNavKey' => '_global'])

@php
    $navigation = $sections['navigation'] ?? [];

    $countSelected = fn (array $permissions) => collect($permissions)
        ->filter(fn (array $perm) => in_array($perm['name'], $selectedPermissions, true))
        ->count();

    $navKeys = collect($navigation)->pluck('key')->all();
    $activeNavKey = in_array($initialNavKey, $navKeys, true)
        ? $initialNavKey
        : ($navKeys[0] ?? '_global');
@endphp

@if ($navigation === [])
    <p class="panel-text">No hay permisos configurados para asignar.</p>
@else
    <div class="perm-master-detail">
        <nav class="perm-area-nav" aria-label="Areas y permisos">
            @foreach ($navigation as $item)
                @php
                    $itemPermissions = $item['permissions'] ?? [];
                    $itemSelected = $countSelected($itemPermissions);
                    $itemTotal = count($itemPermissions);
                    $isActive = $item['key'] === $activeNavKey;
                @endphp
                <button
                    type="button"
                    class="perm-area-nav__item js-perm-area-nav {{ $isActive ? 'is-active' : '' }}"
                    data-area-key="{{ $item['key'] }}"
                    data-search="{{ Str::lower($item['label'].' '.$item['key']) }}"
                    aria-current="{{ $isActive ? 'true' : 'false' }}"
                >
                    <span class="perm-area-nav__label">{{ $item['label'] }}</span>
                    <span
                        class="perm-area-nav__badge js-perm-nav-badge"
                        data-total="{{ $itemTotal }}"
                    >{{ $itemSelected }}/{{ $itemTotal }}</span>
                </button>
            @endforeach
        </nav>

        <div class="perm-area-panels">
            <div class="perm-area-panels__toolbar">
                <div class="perm-search-bar perm-search-bar--panel">
                    <input type="search" id="search-permissions" class="form-input" placeholder="Buscar permiso...">
                </div>
                <label class="user-permissions-advanced">
                    <input type="checkbox" id="toggle-advanced-permissions" class="form-check">
                    <span class="text-small">Mostrar identificadores tecnicos</span>
                </label>
            </div>

            @foreach ($navigation as $item)
                @php
                    $itemPermissions = $item['permissions'] ?? [];
                    $itemSelected = $countSelected($itemPermissions);
                    $itemTotal = count($itemPermissions);
                    $isActive = $item['key'] === $activeNavKey;
                @endphp
                <section
                    class="perm-area-panel js-perm-area-panel {{ $isActive ? 'is-active' : '' }}"
                    data-area-key="{{ $item['key'] }}"
                    data-search="{{ Str::lower($item['label'].' '.$item['key']) }}"
                    @unless ($isActive) hidden @endunless
                >
                    <header class="perm-area-panel__header">
                        <div>
                            <h4 class="perm-area-panel__title">{{ $item['label'] }}</h4>
                            @if (! empty($item['help']))
                                <p class="perm-area-panel__help">{{ $item['help'] }}</p>
                            @endif
                            @if ($item['type'] === 'assigned')
                                <p class="perm-area-panel__meta">
                                    Aplican en el area base:
                                    <strong id="assigned-area-label">Sin area fija</strong>
                                </p>
                            @endif
                        </div>
                        <span
                            class="perm-area-panel__count js-perm-panel-count"
                            data-total="{{ $itemTotal }}"
                        >{{ $itemSelected }}/{{ $itemTotal }} activos</span>
                    </header>

                    @include('admin.users.partials.permission-bulk-actions')

                    @if ($item['type'] === 'assigned')
                        @include('admin.users.partials.permission-modules.switch-list', [
                            'permissions' => $itemPermissions,
                            'selectedPermissions' => $selectedPermissions,
                        ])
                    @elseif ($item['type'] === 'global')
                        @include('admin.users.partials.permission-modules.subgroup-list', [
                            'groups' => $item['groups'] ?? [],
                            'selectedPermissions' => $selectedPermissions,
                        ])
                    @else
                        @include('admin.users.partials.permission-modules.subgroup-list', [
                            'groups' => collect($item['subgroups'] ?? [])->map(fn (array $subgroup) => [
                                'label' => $subgroup['label'] ?? '',
                                'permissions' => $subgroup['permissions'] ?? [],
                            ])->all(),
                            'selectedPermissions' => $selectedPermissions,
                            'areaStyle' => true,
                        ])
                    @endif
                </section>
            @endforeach
        </div>
    </div>
@endif
