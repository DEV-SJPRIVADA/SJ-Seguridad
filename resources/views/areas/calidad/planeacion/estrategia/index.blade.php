<x-app-layout>
    @php
        $hasActiveFilters = ($status ?? '') !== '';
    @endphp

    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Listado de DOFA: filtro por estado y acceso al detalle con objetivos y estrategias. --}}
    <div class="page-section planeacion-page req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('calidad.planeacion.estrategia') }}" class="req-manage-filters">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="estrategia_status">Estado</label>
                                        <x-searchable-select
                                            id="estrategia_status"
                                            name="status"
                                            :options="$statusOptions"
                                            :value="$status ?? ''"
                                            placeholder="Todos"
                                            :allow-clear="true"
                                        />
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('calidad.planeacion.estrategia') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong>{{ $dofas->count() }}</strong>
                            <span>DOFA(s)</span>
                        </p>
                        <div class="cursos-registros-page__table-actions">
                            @if ($canEdit)
                                <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Nuevo DOFA" aria-label="Nuevo DOFA" x-data="" x-on:click="$dispatch('open-modal', 'planeacion-dofa-create')">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Periodo</th>
                                    <th>Rango</th>
                                    <th>Objetivos</th>
                                    <th>Análisis</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dofas as $dofa)
                                    <tr>
                                        <td>{{ $dofa->title }}</td>
                                        <td>{{ $dofa->period_label }}</td>
                                        <td class="planeacion-estrategia__nowrap">
                                            {{ $dofa->starts_on?->format('d/m/Y') }} — {{ $dofa->ends_on?->format('d/m/Y') }}
                                        </td>
                                        <td>{{ $dofa->objectives_count }}</td>
                                        <td>{{ $dofa->items_count }}</td>
                                        <td>
                                            <span class="status-pill {{ $dofa->status === 'active' ? 'status-pill--success' : ($dofa->isArchived() ? 'status-pill--muted' : 'status-pill--warning') }}">
                                                {{ $dofa->statusLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="{{ route('calidad.planeacion.estrategia.show', $dofa) }}" class="cursos-catalogo-page__icon-btn" title="Abrir DOFA" aria-label="Abrir DOFA">
                                                    <x-lucide-arrow-right width="16" height="16" aria-hidden="true" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">Aún no hay DOFA registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canEdit)
        @include('areas.calidad.planeacion.estrategia.partials.dofa-modal', [
            'dofa' => null,
            'quadrants' => \App\Models\QualityPlanning\QualityPlanningDofaItem::QUADRANTS,
        ])    @endif
</x-app-layout>
