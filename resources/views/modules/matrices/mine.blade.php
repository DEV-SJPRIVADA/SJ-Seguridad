<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => config('access.areas.'.$module, 'Matrices')])
    </x-slot>

    {{--
        Lista de matrices del usuario (propias y compartidas).

        Es la pantalla de entrada para quien no administra matrices: buscador por
        nombre o código, filtros por área, creador y estado, y acceso directo a los
        registros de cada matriz.
    --}}
    <div class="page-section matrices-page matrices-mine-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
            @endif

            <header class="matrices-hero">
                <div class="matrices-hero__copy">
                    <p class="matrices-hero__eyebrow">Matrices dinámicas</p>
                    <h1 class="matrices-hero__title">Mis matrices</h1>
                    <p class="matrices-hero__text">
                        Matrices propias y compartidas contigo en {{ config('access.areas.'.$module, 'tu área') }}.
                        Busca por nombre, filtra por área, creador o estado y abre una para registrar o consultar información.
                    </p>
                </div>

                @if ($canCreate)
                    <div class="matrices-hero__actions">
                        <a href="{{ route('matrices.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">
                            <x-lucide-layout-grid width="15" height="15" aria-hidden="true" />
                            Administrar matrices
                        </a>
                        <a href="{{ route('matrices.create', ['module' => $module]) }}" class="btn btn--primary btn--sm">
                            <x-lucide-plus width="15" height="15" aria-hidden="true" />
                            Nueva matriz
                        </a>
                    </div>
                @endif
            </header>

            @php
                $manageQuery = fn (array $overrides = []) => array_filter([
                    'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
                    'area' => array_key_exists('area', $overrides) ? $overrides['area'] : ($filters['area'] ?: null),
                    'owner' => array_key_exists('owner', $overrides) ? $overrides['owner'] : (($filters['owner'] ?? '') !== '' ? $filters['owner'] : null),
                    'status' => array_key_exists('status', $overrides) ? $overrides['status'] : ($filters['status'] ?: null),
                ], fn ($value) => $value !== null && $value !== '');
            @endphp

            <details class="req-manage-filters req-manage-filters__panel matrices-mine__filters" @if ($hasActiveFilters) open @endif>
                <summary class="req-manage-filters__panel-toggle">
                    <span>Filtros</span>
                    @if ($hasActiveFilters)
                        <span class="req-manage-filters__panel-badge">Activos</span>
                    @endif
                </summary>

                <div class="req-manage-filters__panel-body">
                    <div class="req-manage-filters__toolbar">
                        <form method="GET" action="{{ route('matrices.mine', ['module' => $module]) }}" class="req-manage-filters__search-col" role="search">
                            @if ($filters['status'] !== '')
                                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            @endif

                            <div class="req-manage-filters__query-row req-manage-filters__query-row--manage">
                                <div class="req-manage-filters__query-field req-manage-filters__query-field--search">
                                    <label class="req-manage-filters__label" for="matrices-mine-q">Buscar</label>
                                    <input
                                        id="matrices-mine-q"
                                        type="search"
                                        name="q"
                                        value="{{ $filters['q'] }}"
                                        class="form-input"
                                        placeholder="Nombre o código de la matriz…"
                                        autocomplete="off"
                                    >
                                </div>

                                <div class="req-manage-filters__query-field req-manage-filters__query-field--select">
                                    <label class="req-manage-filters__label" for="matrices-mine-area">Área</label>
                                    <x-searchable-select
                                        id="matrices-mine-area"
                                        name="area"
                                        :options="$areaOptions"
                                        :value="$filters['area']"
                                        placeholder="Todas las áreas"
                                        searchPlaceholder="Buscar área…"
                                    />
                                </div>

                                <div class="req-manage-filters__query-field req-manage-filters__query-field--select">
                                    <label class="req-manage-filters__label" for="matrices-mine-owner">Creador</label>
                                    <x-searchable-select
                                        id="matrices-mine-owner"
                                        name="owner"
                                        :options="$ownerOptions"
                                        :value="$filters['owner']"
                                        placeholder="Todos los creadores"
                                        searchPlaceholder="Buscar creador…"
                                    />
                                </div>

                                <div class="req-manage-filters__query-actions">
                                    <span class="req-manage-filters__label req-manage-filters__label--spacer" aria-hidden="true">&nbsp;</span>
                                    <div class="req-manage-filters__query-actions-row">
                                        <button
                                            type="submit"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                            title="Buscar"
                                            aria-label="Buscar matrices"
                                        >
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        @if ($hasActiveFilters)
                                            <a
                                                href="{{ route('matrices.mine', ['module' => $module]) }}"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                                title="Limpiar filtros"
                                                aria-label="Limpiar filtros"
                                            >
                                                <x-lucide-filter-x width="18" height="18" aria-hidden="true" />
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="req-manage-filters__status-row">
                            <p class="req-manage-filters__status-label">Estado</p>
                            <div class="req-manage-filters__pills req-manage-filters__pills--scroll">
                                <a
                                    href="{{ route('matrices.mine', ['module' => $module, ...$manageQuery(['status' => null])]) }}"
                                    class="req-manage-filters__pill {{ ($filters['status'] ?? '') === '' ? 'is-active' : '' }}"
                                >Todos</a>
                                <a
                                    href="{{ route('matrices.mine', ['module' => $module, ...$manageQuery(['status' => 'draft'])]) }}"
                                    class="req-manage-filters__pill {{ ($filters['status'] ?? '') === 'draft' ? 'is-active' : '' }}"
                                >Borrador</a>
                                <a
                                    href="{{ route('matrices.mine', ['module' => $module, ...$manageQuery(['status' => 'published'])]) }}"
                                    class="req-manage-filters__pill {{ ($filters['status'] ?? '') === 'published' ? 'is-active' : '' }}"
                                >Publicada</a>
                            </div>
                        </div>

                        <p class="req-manage-filters__meta req-manage-filters__meta--compact">
                            <strong>{{ number_format($rows->count()) }}</strong>
                            {{ $rows->count() === 1 ? 'matriz' : 'matrices' }}
                            @if ($hasActiveFilters)
                                de <strong>{{ number_format($total) }}</strong>
                            @endif
                            @if ($filters['q'] !== '')
                                · Búsqueda: <strong>{{ $filters['q'] }}</strong>
                            @endif
                            @if ($filters['area'] !== '')
                                @php
                                    $areaLabel = collect($areaOptions)->firstWhere('value', $filters['area'])['label'] ?? $filters['area'];
                                @endphp
                                · Área: <strong>{{ $areaLabel }}</strong>
                            @endif
                            @if ($filters['owner'] !== '')
                                @php
                                    $ownerLabel = collect($ownerOptions)->firstWhere('value', $filters['owner'])['label'] ?? $filters['owner'];
                                @endphp
                                · Creador: <strong>{{ $ownerLabel }}</strong>
                            @endif
                            @if ($filters['status'] !== '')
                                · Estado: <strong>{{ $statusOptions[$filters['status']] ?? $filters['status'] }}</strong>
                            @endif
                        </p>
                    </div>
                </div>
            </details>

            <div class="panel matrices-mine__panel">
                <div class="panel__body panel__body--compact">
                    <div class="data-table-wrap">
                        <table class="data-table matrices-mine__table">
                            <thead>
                                <tr>
                                    <th>Matriz</th>
                                    <th>Área</th>
                                    <th>Creador</th>
                                    <th>Última edición</th>
                                    <th>Estado</th>
                                    <th>Versión</th>
                                    <th>Acceso</th>
                                    <th><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $matrix = $row['matrix'];
                                        $editor = $matrix->updatedBy;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="matrices-mine__matrix">
                                                <a
                                                    href="{{ route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}"
                                                    class="matrices-mine__matrix-name"
                                                >
                                                    {{ $matrix->name }}
                                                </a>
                                                <span class="matrices-mine__matrix-code">{{ $matrix->code ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ config('access.areas.'.$matrix->area_key, 'Sin área') }}</td>
                                        <td>
                                            <div class="matrices-mine__person">
                                                <span class="matrices-mine__avatar" aria-hidden="true">
                                                    {{ mb_strtoupper(mb_substr((string) ($matrix->owner?->name ?? '?'), 0, 1)) }}
                                                </span>
                                                <span class="matrices-mine__person-name">
                                                    {{ $matrix->owner?->name ?? '—' }}
                                                </span>
                                                @if ($row['is_owner'])
                                                    <span class="matrices-chip matrices-chip--owner">Tú</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($editor !== null)
                                                <div class="matrices-mine__person">
                                                    <span class="matrices-mine__person-name">{{ $editor->name }}</span>
                                                </div>
                                            @else
                                                <span class="matrices-mine__muted">Sin registro</span>
                                            @endif
                                            <span class="matrices-mine__date">{{ \App\Support\DisplayDate::dateTime($matrix->updated_at) }}</span>
                                        </td>
                                        <td>
                                            <span class="matrices-status matrices-status--{{ $matrix->status }}">
                                                {{ $matrix->statusLabel() }}
                                            </span>
                                        </td>
                                        <td>{{ $matrix->currentVersion?->version_number ?? '—' }}</td>
                                        <td>
                                            @if ($row['access']->canRegister)
                                                <span class="matrices-chip matrices-chip--ok">Registrar</span>
                                            @else
                                                <span class="matrices-chip matrices-chip--muted">Consultar</span>
                                            @endif
                                        </td>
                                        <td class="table-actions">
                                            <a
                                                href="{{ route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}"
                                                class="cursos-catalogo-page__icon-btn"
                                                title="Abrir matriz"
                                                aria-label="Abrir matriz {{ $matrix->name }}"
                                            >
                                                <x-lucide-arrow-right width="16" height="16" aria-hidden="true" />
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="matrices-mine__empty">
                                                <span class="matrices-mine__empty-icon" aria-hidden="true">
                                                    <x-lucide-inbox width="26" height="26" />
                                                </span>
                                                @if ($hasActiveFilters)
                                                    <p class="matrices-mine__empty-title">No hay matrices que coincidan con los filtros.</p>
                                                    <a href="{{ route('matrices.mine', ['module' => $module]) }}" class="btn btn--secondary btn--sm">
                                                        Limpiar filtros
                                                    </a>
                                                @else
                                                    <p class="matrices-mine__empty-title">No tienes matrices asignadas.</p>
                                                    <p class="matrices-mine__empty-text">
                                                        Cuando alguien comparta una matriz contigo aparecerá en esta lista.
                                                    </p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
