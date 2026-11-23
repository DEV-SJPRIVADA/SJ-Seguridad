<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => config('access.areas.'.$module, 'Matrices')])
    </x-slot>

    <div class="page-section matrices-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
            @endif

            <header class="matrices-hero">
                <div class="matrices-hero__copy">
                    <p class="matrices-hero__eyebrow">Matrices dinámicas</p>
                    <h1 class="matrices-hero__title">Formularios propios de {{ config('access.areas.'.$module, 'tu área') }}</h1>
                    <p class="matrices-hero__text">
                        Crea matrices con los campos que necesites, compártelas con áreas o personas y consulta
                        los registros en un listado filtrable con exportación a Excel.
                    </p>
                </div>

                @if ($canCreate)
                    <a href="{{ route('matrices.create', ['module' => $module]) }}" class="btn btn--primary">
                        Nueva matriz
                    </a>
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
                        <form method="GET" action="{{ route('matrices.index', ['module' => $module]) }}" class="req-manage-filters__search-col" role="search">
                            @if ($filters['status'] !== '')
                                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            @endif

                            <div class="req-manage-filters__query-row req-manage-filters__query-row--manage">
                                <div class="req-manage-filters__query-field req-manage-filters__query-field--search">
                                    <label class="req-manage-filters__label" for="matrices-index-q">Buscar</label>
                                    <input
                                        id="matrices-index-q"
                                        type="search"
                                        name="q"
                                        value="{{ $filters['q'] }}"
                                        class="form-input"
                                        placeholder="Nombre o código de la matriz…"
                                        autocomplete="off"
                                    >
                                </div>

                                <div class="req-manage-filters__query-field req-manage-filters__query-field--select">
                                    <label class="req-manage-filters__label" for="matrices-index-area">Área</label>
                                    <x-searchable-select
                                        id="matrices-index-area"
                                        name="area"
                                        :options="$areaOptions"
                                        :value="$filters['area']"
                                        placeholder="Todas las áreas"
                                        searchPlaceholder="Buscar área…"
                                    />
                                </div>

                                <div class="req-manage-filters__query-field req-manage-filters__query-field--select">
                                    <label class="req-manage-filters__label" for="matrices-index-owner">Creador</label>
                                    <x-searchable-select
                                        id="matrices-index-owner"
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
                                                href="{{ route('matrices.index', ['module' => $module]) }}"
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
                                    href="{{ route('matrices.index', ['module' => $module, ...$manageQuery(['status' => null])]) }}"
                                    class="req-manage-filters__pill {{ ($filters['status'] ?? '') === '' ? 'is-active' : '' }}"
                                >Todos</a>
                                <a
                                    href="{{ route('matrices.index', ['module' => $module, ...$manageQuery(['status' => 'draft'])]) }}"
                                    class="req-manage-filters__pill {{ ($filters['status'] ?? '') === 'draft' ? 'is-active' : '' }}"
                                >Borrador</a>
                                <a
                                    href="{{ route('matrices.index', ['module' => $module, ...$manageQuery(['status' => 'published'])]) }}"
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

            @if ($rows->isEmpty())
                <div class="panel matrices-empty">
                    <div class="matrices-empty__icon" aria-hidden="true">
                        <x-lucide-layout-grid width="28" height="28" />
                    </div>
                    @if ($hasActiveFilters)
                        <h2 class="matrices-empty__title">No hay matrices que coincidan con los filtros</h2>
                        <p class="matrices-empty__text">Ajusta o limpia los filtros para ver más matrices.</p>
                        <a href="{{ route('matrices.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">
                            Limpiar filtros
                        </a>
                    @else
                        <h2 class="matrices-empty__title">Aún no hay matrices para mostrar</h2>
                        <p class="matrices-empty__text">
                            @if ($canCreate)
                                Crea la primera matriz de tu área y compártela para que otros usuarios la diligencien.
                            @else
                                Cuando alguien comparta una matriz contigo aparecerá en esta lista.
                            @endif
                        </p>
                        @if ($canCreate)
                            <a href="{{ route('matrices.create', ['module' => $module]) }}" class="btn btn--primary btn--sm">
                                Crear matriz
                            </a>
                        @endif
                    @endif
                </div>
            @else
                <div class="matrices-grid">
                    @foreach ($rows as $row)
                        @php
                            $matrix = $row['matrix'];
                            $access = $row['access'];
                        @endphp

                        <article class="matrices-card">
                            <header class="matrices-card__header">
                                <div class="matrices-card__heading">
                                    <span class="matrices-card__code">{{ $matrix->code ?? '—' }}</span>
                                    <h2 class="matrices-card__title">{{ $matrix->name }}</h2>
                                </div>
                                <span class="matrices-status matrices-status--{{ $matrix->status }}">
                                    {{ $matrix->statusLabel() }}
                                </span>
                            </header>

                            @if ($matrix->description)
                                <p class="matrices-card__description">{{ \Illuminate\Support\Str::limit($matrix->description, 180) }}</p>
                            @endif

                            <dl class="matrices-card__meta">
                                <div>
                                    <dt>Área</dt>
                                    <dd>{{ config('access.areas.'.$matrix->area_key, 'Sin área') }}</dd>
                                </div>
                                <div>
                                    <dt>Creador</dt>
                                    <dd>{{ $matrix->owner?->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Última edición</dt>
                                    <dd>{{ $matrix->updatedBy?->name ?? $matrix->owner?->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Registros</dt>
                                    <dd>{{ number_format($row['records_count'], 0, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt>Versión</dt>
                                    <dd>{{ $matrix->currentVersion?->version_number ?? '—' }}</dd>
                                </div>
                            </dl>

                            <div class="matrices-card__chips">
                                @if ($row['is_owner'])
                                    <span class="matrices-chip matrices-chip--owner">Creador</span>
                                @endif
                                @if ($access->canRegister)
                                    <span class="matrices-chip matrices-chip--ok">Registrar</span>
                                @endif
                                @if ($access->canEditOthers)
                                    <span class="matrices-chip">Edita todo</span>
                                @elseif ($access->canViewOthers)
                                    <span class="matrices-chip">Ve todo</span>
                                @else
                                    <span class="matrices-chip matrices-chip--muted">Solo lo propio</span>
                                @endif
                            </div>

                            <footer class="matrices-card__actions">
                                @if ($access->canRegister && ! $matrix->isArchived())
                                    <a href="{{ route('matrices.submissions.create', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--primary btn--sm">
                                        Registrar
                                    </a>
                                @endif
                                <a href="{{ route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary btn--sm">
                                    Registros
                                </a>

                                @if ($row['can_manage'])
                                    <a href="{{ route('matrices.share.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary btn--sm">
                                        Compartir
                                    </a>
                                    <a href="{{ route('matrices.edit', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary btn--sm">
                                        Definición
                                    </a>

                                    @if ($matrix->isDraft())
                                        <form method="POST" action="{{ route('matrices.publish', ['module' => $module, 'matrix' => $matrix->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn--info btn--sm">Publicar</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('matrices.archive', ['module' => $module, 'matrix' => $matrix->id]) }}" onsubmit="return confirm('¿Archivar la matriz «{{ $matrix->name }}»? Los registros se conservan.');">
                                        @csrf
                                        <button type="submit" class="btn btn--danger btn--sm">Archivar</button>
                                    </form>
                                @endif
                            </footer>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
