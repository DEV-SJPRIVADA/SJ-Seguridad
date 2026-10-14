<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.inasistencias.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Histórico de cartas de inasistencia: solo registro (sin archivo Word). --}}
    <div class="page-section req-manage-page inasistencias-page">
        <div class="app-container">
            <div class="panel req-manage-shell">
                <div class="panel__body panel__body--compact">
                    <details class="req-manage-filters" open>
                        <summary class="req-manage-filters__summary">
                            <span>Filtros del histórico</span>
                        </summary>
                        <form method="get" action="{{ route('gestion-humana.inasistencias.historico') }}" class="req-manage-filters__form">
                            <div class="req-manage-filters__grid">
                                <div class="form-field">
                                    <label class="form-label" for="filter_q">Cédula</label>
                                    <input
                                        id="filter_q"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Buscar cédula…"
                                    >
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_firma">Firma</label>
                                    <input
                                        id="filter_firma"
                                        type="search"
                                        name="firma"
                                        class="form-input"
                                        value="{{ $filters['firma'] }}"
                                        placeholder="Nombre o cargo firma…"
                                    >
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_fecha_desde">Generado desde</label>
                                    <input id="filter_fecha_desde" type="date" name="fecha_desde" class="form-input" value="{{ $filters['fecha_desde'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_fecha_hasta">Generado hasta</label>
                                    <input id="filter_fecha_hasta" type="date" name="fecha_hasta" class="form-input" value="{{ $filters['fecha_hasta'] }}">
                                </div>
                            </div>
                            <div class="req-manage-filters__actions" role="group" aria-label="Acciones de filtro">
                                <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Aplicar filtros" aria-label="Aplicar filtros">
                                    <x-lucide-search width="18" height="18" aria-hidden="true" />
                                </button>
                                <a
                                    href="{{ route('gestion-humana.inasistencias.historico') }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Limpiar filtros"
                                    aria-label="Limpiar filtros"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                                <x-export-excel
                                    route="{{ $exportUrl }}"
                                    label=""
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Exportar Excel"
                                    aria-label="Exportar Excel del histórico"
                                />
                            </div>
                        </form>
                    </details>

                    <div class="data-table-wrap">
                        {{-- Tabla paginada en servidor (sin js-datatable: evita conflicto de columnas en vacío). --}}
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Cédula</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th>Ciudad</th>
                                    <th>Fecha ini aus</th>
                                    <th>Días</th>
                                    <th>Firma</th>
                                    <th>Cargo firma</th>
                                    <th>Generado</th>
                                    <th>Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($records as $row)
                                    <tr>
                                        <td>{{ $row->document_number }}</td>
                                        <td>{{ $row->full_name ?: '—' }}</td>
                                        <td>{{ $row->cargo ?: '—' }}</td>
                                        <td>{{ $row->ciudad ?: '—' }}</td>
                                        <td><x-date-table :value="$row->fecha_ini_aus" /></td>
                                        <td>{{ $row->dias }}</td>
                                        <td>{{ $row->firma_name ?: '—' }}</td>
                                        <td>{{ $row->cargo_firma ?: '—' }}</td>
                                        <td><x-date-table :value="$row->generated_at" /></td>
                                        <td>{{ $row->generator?->name ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-muted">No hay registros de inasistencias generadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="req-manage-pagination">
                        {{ $records->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
