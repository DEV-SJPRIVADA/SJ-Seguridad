<x-app-layout>
    <div class="page-section admin-users-page">
        <div class="app-container page-stack admin-users-page__stack">
            <div class="panel users-panel">
                <div class="panel__body panel__body--soft">
                    <div class="action-row">
                        <div>
                            <p class="eyebrow">Administracion de usuarios</p>
                            <p class="panel-text">Selecciona un usuario para revisar areas y permisos. Desde el panel derecho puedes entrar a editar.</p>
                        </div>

                        <div class="form-actions__group">
                            <div class="users-kpi-stack">
                                <div class="card kpi-card">
                                    <p class="kpi-card__label text-muted">Total usuarios</p>
                                    <p class="kpi-card__value">{{ $stats['total'] }}</p>
                                </div>
                                <div class="card card--success kpi-card">
                                    <p class="kpi-card__label kpi-card__label--success">Activos</p>
                                    <p class="kpi-card__value kpi-card__value--success">{{ $stats['active'] }}</p>
                                </div>
                                <div class="card card--danger kpi-card">
                                    <p class="kpi-card__label kpi-card__label--danger">Bloqueados</p>
                                    <p class="kpi-card__value kpi-card__value--danger">{{ $stats['inactive'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="users-grid">
                    <aside class="users-sidebar">
                        <div class="panel__header">
                            <p class="text-caption">Lista de usuarios</p>
                            <p class="panel-text">{{ $users->total() }} registros encontrados</p>
                            <form method="GET" action="{{ route('admin.users.index') }}" class="users-list-toolbar block-spaced-sm">
                                <div class="search-bar">
                                    <input
                                        type="text"
                                        name="q"
                                        value="{{ $filters['q'] ?? '' }}"
                                        placeholder="Buscar por nombre o correo"
                                        class="form-input"
                                    >
                                    <button type="submit" class="btn btn--secondary">
                                        Buscar
                                    </button>
                                </div>
                                <label class="users-filter-toggle">
                                    <input
                                        type="checkbox"
                                        name="include_inactive"
                                        value="1"
                                        @checked($filters['include_inactive'] ?? false)
                                        onchange="this.form.submit()"
                                    >
                                    <span>Mostrar usuarios inactivos</span>
                                </label>
                            </form>
                        </div>

                        <div class="users-list">
                            @forelse ($users as $user)
                                <a
                                    href="{{ route('admin.users.index', array_filter([
                                        'q' => $filters['q'] ?? null,
                                        'include_inactive' => ($filters['include_inactive'] ?? false) ? '1' : null,
                                        'selected' => $user->id,
                                        'page' => $users->currentPage(),
                                    ])) }}"
                                    class="users-list-item {{ $selectedUser?->id === $user->id ? 'users-list-item--active' : '' }}"
                                >
                                    <div class="users-list-item__top">
                                        <div>
                                            <p class="users-list-item__title">{{ $user->name }}</p>
                                            <p class="users-list-item__meta">{{ $user->email }}</p>
                                            <p class="users-list-item__meta">{{ $user->roles->pluck('name')->implode(', ') ?: 'Sin rol asignado' }}</p>
                                        </div>

                                        <span class="status-dot {{ $user->is_active ? 'status-dot--success' : 'status-dot--danger' }}"></span>
                                    </div>

                                    <p class="users-list-item__meta">
                                        {{ $user->areaLabel() ?: 'Sin area base asignada' }}
                                    </p>

                                    @if ($user->must_change_password)
                                        <span class="status-pill status-pill--warning block-spaced">Cambio pendiente</span>
                                    @endif
                                </a>
                            @empty
                                <div class="panel__body text-small text-muted">
                                    No hay usuarios para mostrar con el filtro actual.
                                </div>
                            @endforelse
                        </div>
                    </aside>

                    <section class="users-content">
                        @if ($selectedUser)
                            @php
                                $sections = $permissionForm['sections'] ?? [];
                                $permissionLabels = collect($sections['assigned_area']['permissions'] ?? [])
                                    ->merge(collect($sections['global']['groups'] ?? [])->flatMap(fn (array $group) => $group['permissions'] ?? []))
                                    ->merge(collect($sections['other_areas']['areas'] ?? [])->flatMap(fn (array $area) => $area['permissions'] ?? []))
                                    ->keyBy('name');

                                $assignedPermissionLabels = $selectedUser->permissions
                                    ->pluck('name')
                                    ->map(fn (string $name) => $permissionLabels->get($name, ['label' => $name])['label'])
                                    ->sort()
                                    ->values();
                            @endphp

                            <div class="users-content__header">
                                <div class="panel__header">
                                    <div class="action-row">
                                        <div>
                                            <p class="text-caption">Resumen del usuario</p>
                                            <h3 class="page-title page-title--sm title-spaced">{{ $selectedUser->name }}</h3>
                                            <p class="page-subtitle">{{ $selectedUser->email }}</p>
                                        </div>

                                        <div class="form-actions__group">
                                            <span class="status-pill {{ $selectedUser->is_active ? 'status-pill--success' : 'status-pill--danger' }}">
                                                {{ $selectedUser->is_active ? 'Activo' : 'Bloqueado' }}
                                            </span>
                                            <span class="status-pill status-pill--muted">
                                                {{ $selectedUser->roles->pluck('name')->implode(', ') ?: 'Sin rol' }}
                                            </span>
                                            <a href="{{ route('admin.users.edit', $selectedUser) }}" class="btn btn--info">
                                                Editar usuario
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="users-content__body">
                                <div class="panel__body split-grid">
                                    <div class="section-stack">
                                        <div class="card card--muted">
                                            <h4 class="panel-title">Ficha operativa</h4>
                                            <div class="detail-grid block-spaced">
                                                <div class="card">
                                                    <p class="text-caption">Area base</p>
                                                    <p class="text-small text-small--strong block-spaced-sm">{{ $selectedUser->areaLabel() ?: 'Sin area asignada' }}</p>
                                                </div>
                                                <div class="card">
                                                    <p class="text-caption">Ultimo acceso</p>
                                                    <p class="text-small text-small--strong block-spaced-sm">{{ $selectedUser->last_login_at?->format('Y-m-d H:i') ?? 'Sin acceso registrado' }}</p>
                                                </div>
                                                <div class="card">
                                                    <p class="text-caption">Creado por</p>
                                                    <p class="text-small text-small--strong block-spaced-sm">{{ $selectedUser->creator?->name ?? 'Seeder / sistema' }}</p>
                                                </div>
                                                <div class="card">
                                                    <p class="text-caption">Cambio de contrasena</p>
                                                    <p class="text-small text-small--strong block-spaced-sm">{{ $selectedUser->must_change_password ? 'Pendiente al siguiente ingreso' : 'No requerido' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card card--muted">
                                            <h4 class="panel-title">Permisos asignados</h4>
                                            @if (! empty($accessSummary['notes'] ?? []))
                                                <div class="user-access-notes block-spaced-sm">
                                                    @foreach ($accessSummary['notes'] as $note)
                                                        <p class="text-small text-muted" style="margin: 0 0 0.35rem;">{{ $note }}</p>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if ($assignedPermissionLabels->isNotEmpty())
                                                <ul class="user-permission-tags">
                                                    @foreach ($assignedPermissionLabels as $label)
                                                        <li class="user-permission-tag">{{ $label }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-small text-muted block-spaced-sm">Este usuario no tiene permisos adicionales asignados.</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="section-stack">
                                        <div class="card card--info">
                                            <h4 class="panel-title">Area base</h4>
                                            <p class="text-small text-small--info block-spaced-sm">{{ $selectedUser->areaLabel() ?: 'Sin area asignada' }}</p>
                                        </div>

                                        <div class="card card--warning">
                                            <h4 class="panel-title warning-text">Accion rapida</h4>
                                            <p class="text-small block-spaced warning-text">
                                                Desde la edicion puedes ajustar rol, area base, activacion y toda la matriz detallada de permisos del usuario.
                                            </p>
                                            <a href="{{ route('admin.users.edit', $selectedUser) }}" class="btn btn--info">
                                                Abrir formulario de edicion
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="panel__body text-small text-muted">
                                No hay usuarios disponibles para administrar.
                            </div>
                        @endif
                    </section>
                </div>

                <div class="panel__body panel__body--compact panel-divider-top">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
