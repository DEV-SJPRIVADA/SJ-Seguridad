<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Empleados</h2>
                <p class="panel-text">Gestion humana — lista de espera y ficha de empleados contratados</p>
            </div>
        </div>
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== '';

        $entriesQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'estado' => array_key_exists('estado', $overrides) ? $overrides['estado'] : ($filters['estado'] ?: null),
            'fecha_desde' => array_key_exists('fecha_desde', $overrides) ? $overrides['fecha_desde'] : null,
            'fecha_hasta' => array_key_exists('fecha_hasta', $overrides) ? $overrides['fecha_hasta'] : null,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="page-section ficha-empleados-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-page__alert">{{ session('status') }}</div>
            @endif

            @if ($errors->has('export'))
                <div class="alert alert--danger ficha-empleados-page__alert">{{ $errors->first('export') }}</div>
            @endif

            @if ($errors->has('import_file'))
                <div class="alert alert--danger ficha-empleados-page__alert">{{ $errors->first('import_file') }}</div>
            @endif

            <div class="panel ficha-empleados-panel">
                <div class="panel__body panel__body--compact">
                    <div class="req-manage-filters ficha-empleados-filters">
                        <div class="req-manage-filters__head ficha-empleados-filters__head">
                            <div class="panel-heading-row panel-heading-row--wrap">
                                <h3 class="panel-title">Listado de empleados</h3>
                                <p class="panel-text">Cedula, nombre o codigo de requisicion</p>
                            </div>
                            <div class="ficha-empleados-filters__head-actions">
                                @if ($hasActiveFilters)
                                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index', ['estado' => $filters['estado']]) }}" class="btn btn--secondary btn--sm">Limpiar busqueda</a>
                                @endif

                                @if (($filters['estado'] ?? 'pendientes') === 'en_ficha')
                                    <button
                                        type="button"
                                        class="ficha-empleados-filters__bulk-icon"
                                        title="Plantilla masivos — exportar e importar"
                                        aria-label="Plantilla masivos — exportar e importar"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'ficha-masivos')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 3v12"/>
                                            <path d="m7 8 5-5 5 5"/>
                                            <path d="M5 21h14"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="req-manage-filters__toolbar ficha-empleados-filters__toolbar">
                            <form method="GET" class="ficha-empleados-filters__form req-manage-filters__search-col">
                                @if ($filters['estado'] ?? '')
                                    <input type="hidden" name="estado" value="{{ $filters['estado'] }}">
                                @endif
                                <label class="req-manage-filters__label" for="ficha-search-input">Buscar</label>
                                <div class="req-manage-filters__search-group ficha-empleados-filters__search-group">
                                    <input
                                        id="ficha-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Cedula, nombre o codigo de requisicion"
                                    >
                                    <button type="submit" class="btn btn--primary btn--sm">Buscar</button>
                                </div>
                            </form>

                            <div class="req-manage-filters__status-col ficha-empleados-filters__status-col">
                                <p class="req-manage-filters__status-label">Estado</p>
                                <div class="req-manage-filters__pills ficha-empleados-filters__pills">
                                    @foreach ($estadoLabels as $estadoKey => $estadoLabel)
                                        <a
                                            href="{{ route('gestion-humana.ficha-empleados.employees.index', $entriesQuery(['estado' => $estadoKey])) }}"
                                            class="req-manage-filters__pill {{ $estadoKey === 'en_ficha' ? 'status-pill--success' : 'status-pill--warning' }} {{ ($filters['estado'] ?? 'pendientes') === $estadoKey ? 'is-active' : '' }}"
                                        >{{ $estadoLabel }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <p class="req-manage-filters__meta ficha-empleados-filters__meta">
                            <strong>{{ number_format($entries->count()) }}</strong>
                            {{ $entries->count() === 1 ? 'registro' : 'registros' }}
                            @if ($filters['q'] ?? '')
                                · Busqueda: <strong>{{ $filters['q'] }}</strong>
                            @endif
                        </p>
                    </div>

                    <div class="data-table-wrap ficha-empleados-page__table-wrap">
                        <table class="data-table js-datatable" data-dt-responsive="false" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Codigo requisicion</th>
                                    <th>Cedula</th>
                                    <th>Nombre completo</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Fecha contratacion</th>
                                    <th>Ficha</th>
                                    @if (($filters['estado'] ?? 'pendientes') === 'en_ficha')
                                        <th>Agregado a ficha</th>
                                        <th>Agregado por</th>
                                    @else
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($entries as $entry)
                                    <tr>
                                        <td>{{ $entry->requisitionCode() ?: '—' }}</td>
                                        <td>{{ $entry->hired_document }}</td>
                                        <td>{{ $entry->hired_full_name }}</td>
                                        <td>{{ $entry->positionName() ?: '—' }}</td>
                                        <td>{{ $entry->clientName() ?: '—' }}</td>
                                        <td>{{ $entry->cityName() ?: '—' }}</td>
                                        <td><x-date-table :value="$entry->requisition?->hiring_date" /></td>
                                        <td>
                                            @if ($canManage)
                                                <a href="{{ route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry) }}" class="btn btn--secondary btn--sm">Completar ficha</a>
                                            @else
                                                {{ $entry->profile ? 'Si' : '—' }}
                                            @endif
                                        </td>
                                        @if ($entry->moved_to_ficha_at !== null)
                                            <td><x-date-table :value="$entry->moved_to_ficha_at" datetime /></td>
                                            <td>{{ $entry->movedBy?->name ?: '—' }}</td>
                                        @else
                                            <td class="table-actions">
                                                @if ($canManage)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('gestion-humana.ficha-empleados.employees.promote', $entry) }}"
                                                        class="js-promote-ficha-entry"
                                                        data-confirm-title="Agregar a ficha empleados"
                                                        data-confirm-text="¿Agregar a {{ $entry->hired_full_name }} a Ficha empleados? El registro dejara de aparecer en Pendientes."
                                                    >
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn--primary btn--sm">Agregar a ficha empleados</button>
                                                    </form>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">No hay registros para este filtro.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if (($filters['estado'] ?? 'pendientes') === 'en_ficha')
                <x-modal
                    name="ficha-masivos"
                    maxWidth="md"
                    :show="$errors->has('export') || $errors->has('import_file')"
                    focusable
                >
                    <div class="modal-card form-stack ficha-empleados-masivos-modal">
                        <div class="ficha-empleados-masivos-modal__header">
                            <div>
                                <h3 class="panel-title">Plantilla masivos</h3>
                                <p class="panel-text">Exportar empleados en ficha o importar actualizaciones masivas</p>
                            </div>
                            <button
                                type="button"
                                class="ficha-empleados-masivos-modal__close"
                                aria-label="Cerrar"
                                x-on:click="$dispatch('close-modal', 'ficha-masivos')"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 6 6 18"/>
                                    <path d="m6 6 12 12"/>
                                </svg>
                            </button>
                        </div>

                        @if ($errors->has('export'))
                            <div class="alert alert--danger">{{ $errors->first('export') }}</div>
                        @endif

                        @if ($errors->has('import_file'))
                            <div class="alert alert--danger">{{ $errors->first('import_file') }}</div>
                        @endif

                        <div class="ficha-empleados-masivos-modal__section">
                            <p class="ficha-empleados-masivos-modal__subtitle">Exportar</p>
                            <p class="ficha-empleados-masivos-modal__note">Sin rango de fechas se exportan solo empleados activos.</p>
                            <form method="GET" action="{{ route('gestion-humana.ficha-empleados.employees.export') }}" class="ficha-empleados-masivos-modal__export">
                                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                                <div class="ficha-empleados-masivos-modal__date-row">
                                    <label class="ficha-empleados-masivos-modal__field-label" for="ficha-export-desde">Desde</label>
                                    <input type="date" id="ficha-export-desde" name="fecha_desde" class="form-input" value="{{ request('fecha_desde') }}">
                                    <label class="ficha-empleados-masivos-modal__field-label" for="ficha-export-hasta">Hasta</label>
                                    <input type="date" id="ficha-export-hasta" name="fecha_hasta" class="form-input" value="{{ request('fecha_hasta') }}">
                                </div>
                                <button type="submit" class="btn btn--secondary btn--sm">Exportar plantilla</button>
                            </form>
                        </div>

                        @if ($canManage)
                            <div class="ficha-empleados-masivos-modal__section ficha-empleados-masivos-modal__section--import">
                                <p class="ficha-empleados-masivos-modal__subtitle">Importar</p>
                                <a href="{{ route('gestion-humana.ficha-empleados.employees.import-template') }}" class="btn btn--secondary btn--sm">Descargar plantilla vacia</a>
                                <form method="POST" action="{{ route('gestion-humana.ficha-empleados.employees.import') }}" enctype="multipart/form-data" class="ficha-empleados-masivos-modal__import">
                                    @csrf
                                    <label class="ficha-empleados-masivos-modal__file">
                                        <input type="file" name="import_file" accept=".xlsx" class="ficha-empleados-masivos-modal__file-input" required data-ficha-import-file>
                                        <span class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__file-trigger">Elegir archivo</span>
                                        <span class="ficha-empleados-masivos-modal__file-name" data-ficha-import-name>Sin archivo</span>
                                    </label>
                                    <button type="submit" class="btn btn--primary btn--sm">Importar</button>
                                </form>
                            </div>
                        @endif

                        <div class="content-actions content-actions--end ficha-empleados-masivos-modal__footer">
                            <button type="button" class="btn btn--secondary btn--sm" x-on:click="$dispatch('close-modal', 'ficha-masivos')">Cerrar</button>
                        </div>
                    </div>
                </x-modal>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-ficha-import-file]').forEach(function (input) {
                    var nameEl = input.closest('.ficha-empleados-masivos-modal__file')?.querySelector('[data-ficha-import-name]');
                    if (!nameEl) {
                        return;
                    }

                    input.addEventListener('change', function () {
                        var file = input.files && input.files[0];
                        nameEl.textContent = file ? file.name : 'Sin archivo';
                    });
                });

                if (typeof Swal === 'undefined') {
                    return;
                }

                document.querySelectorAll('.js-promote-ficha-entry').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (form.dataset.confirmed === '1') {
                            return;
                        }

                        event.preventDefault();

                        Swal.fire({
                            icon: 'question',
                            title: form.dataset.confirmTitle || 'Confirmar accion',
                            text: form.dataset.confirmText || '¿Continuar?',
                            showCancelButton: true,
                            confirmButtonText: 'Agregar',
                            cancelButtonText: 'Cancelar',
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                form.dataset.confirmed = '1';
                                form.submit();
                            }
                        });
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
