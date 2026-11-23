<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => config('access.areas.'.$module, 'Matrices')])
    </x-slot>

    {{--
        Lista simple de matrices del usuario (propias y compartidas).

        Es la pantalla de entrada para quien no administra matrices: tabla con
        filtro por nombre o código y un enlace directo a los registros de cada una.
    --}}
    <div class="page-section matrices-page matrices-mine-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner">
                <h2 class="page-title">Mis matrices</h2>
                <p class="page-subtitle">
                    Matrices propias y compartidas contigo en {{ config('access.areas.'.$module, 'tu área') }}.
                    Selecciona una para ver o registrar información.
                </p>
            </div>

            <div class="panel">
                <div class="panel__body panel__body--compact">
                    <div class="matrices-mine__toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="matrices-mine-count">{{ $rows->count() }}</strong>
                            <span>matriz(ces)</span>
                        </p>
                        <div class="form-field matrices-mine__filter">
                            <label class="form-label" for="matrices-mine-filter">Filtrar</label>
                            <input
                                id="matrices-mine-filter"
                                type="search"
                                class="form-input"
                                placeholder="Nombre o código…"
                                data-matrices-mine-filter
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="data-table-wrap">
                        <table class="data-table matrices-mine__table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Matriz</th>
                                    <th>Área</th>
                                    <th>Estado</th>
                                    <th>Versión</th>
                                    <th>Acceso</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody data-matrices-mine-body>
                                @forelse ($rows as $row)
                                    @php $matrix = $row['matrix']; @endphp
                                    <tr data-matrix-row data-search="{{ mb_strtoupper(($matrix->code ?? '').' '.$matrix->name) }}">
                                        <td>{{ $matrix->code ?? '—' }}</td>
                                        <td class="matrices-mine__name">{{ $matrix->name }}</td>
                                        <td>{{ config('access.areas.'.$matrix->area_key, 'Sin área') }}</td>
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
                                                aria-label="Abrir matriz"
                                            >
                                                <x-lucide-arrow-right width="16" height="16" aria-hidden="true" />
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">No tienes matrices asignadas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($canCreate)
                        <div class="matrices-mine__admin-link">
                            <a href="{{ route('matrices.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">
                                <x-lucide-layout-grid width="15" height="15" aria-hidden="true" />
                                Administrar matrices
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filtro local: la lista es corta (matrices asignadas al usuario).
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.querySelector('[data-matrices-mine-filter]');
            const body = document.querySelector('[data-matrices-mine-body]');
            const counter = document.getElementById('matrices-mine-count');

            if (! input || ! body) {
                return;
            }

            input.addEventListener('input', function () {
                const term = input.value.trim().toUpperCase();
                let visible = 0;

                body.querySelectorAll('[data-matrix-row]').forEach(function (row) {
                    const match = term === '' || (row.dataset.search || '').includes(term);
                    row.hidden = ! match;
                    if (match) {
                        visible++;
                    }
                });

                if (counter) {
                    counter.textContent = String(visible);
                }
            });
        });
    </script>
</x-app-layout>
