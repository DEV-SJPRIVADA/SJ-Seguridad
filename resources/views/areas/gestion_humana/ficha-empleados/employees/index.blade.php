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
        $currentEstado = $filters['estado'] ?? 'en_ficha';
        $hasActiveFilters = ($filters['q'] ?? '') !== '';

        $entriesQuery = fn (array $overrides = []) => array_filter([
            'q' => array_key_exists('q', $overrides) ? $overrides['q'] : ($filters['q'] ?: null),
            'estado' => array_key_exists('estado', $overrides) ? $overrides['estado'] : ($filters['estado'] ?: null),
            'fecha_desde' => array_key_exists('fecha_desde', $overrides) ? $overrides['fecha_desde'] : null,
            'fecha_hasta' => array_key_exists('fecha_hasta', $overrides) ? $overrides['fecha_hasta'] : null,
        ], fn ($value) => $value !== null && $value !== '');

        $pendingActive = $currentEstado === 'pendientes';
        $pendingHref = $pendingActive
            ? route('gestion-humana.ficha-empleados.employees.index', array_filter(['q' => $filters['q'] ?: null]))
            : route('gestion-humana.ficha-empleados.employees.index', $entriesQuery(['estado' => 'pendientes']));
    @endphp

    <div class="page-section ficha-empleados-page">
        <div class="app-container">
            @if (session('status'))
                @php
                    $importResult = session('import_result');
                    $importHasErrors = is_array($importResult) && ($importResult['failed'] ?? 0) > 0;
                @endphp
                <div class="alert {{ $importHasErrors ? 'alert--warning' : 'alert--success' }} ficha-empleados-page__alert">
                    {{ session('status') }}
                </div>
            @endif

            @if (is_array(session('import_result')) && (session('import_result.failed') ?? 0) > 0)
                @php
                    $importResult = session('import_result');
                @endphp
                <div class="panel ficha-empleados-import-result">
                    <div class="panel__header">
                        <h3 class="panel-title">Detalle de errores de importacion</h3>
                        <p class="panel-text">
                            Se procesaron {{ number_format(($importResult['imported'] ?? 0) + ($importResult['updated'] ?? 0)) }} filas correctamente.
                            {{ number_format($importResult['failed'] ?? 0) }} filas fallaron.
                            @if (($importResult['empty_rows'] ?? 0) > 0)
                                {{ number_format($importResult['empty_rows']) }} filas sin cedula fueron ignoradas.
                            @endif
                        </p>
                    </div>
                    <div class="panel__body">
                        <ul class="ficha-empleados-import-result__errors">
                            @foreach ($importResult['errors'] ?? [] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @if ($importResult['errors_truncated'] ?? false)
                            <p class="ficha-empleados-import-result__truncated">
                                Mostrando {{ count($importResult['errors'] ?? []) }} de {{ number_format($importResult['errors_total'] ?? 0) }} errores.
                                El resto quedo registrado en el log de la aplicacion.
                            </p>
                        @endif
                    </div>
                </div>
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
                                @if ($canManage)
                                    <a href="{{ route('gestion-humana.ficha-empleados.employees.create') }}" class="btn btn--primary btn--sm">Nuevo empleado</a>
                                @endif

                                @if ($hasActiveFilters)
                                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index', array_filter(['estado' => $currentEstado === 'en_ficha' ? null : $currentEstado])) }}" class="btn btn--secondary btn--sm">Limpiar busqueda</a>
                                @endif

                                @if ($currentEstado === 'en_ficha')
                                    <button
                                        type="button"
                                        class="ficha-empleados-filters__bulk-icon"
                                        title="Plantilla masivos — exportar e importar"
                                        aria-label="Plantilla masivos — exportar e importar"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'ficha-masivos')"
                                    >
                                        <x-lucide-icon name="upload" :size="20" />
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="req-manage-filters__toolbar ficha-empleados-filters__toolbar">
                            <form method="GET" class="ficha-empleados-filters__form req-manage-filters__search-col">
                                @if ($currentEstado !== 'en_ficha')
                                    <input type="hidden" name="estado" value="{{ $currentEstado }}">
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
                                <a
                                    href="{{ $pendingHref }}"
                                    class="ficha-empleados-filters__pending-link {{ $pendingActive ? 'is-active' : '' }}"
                                    title="{{ $pendingActive ? 'Volver a empleados en ficha' : 'Ver pendientes' }}"
                                    aria-label="Pendientes: {{ number_format($pendingCount) }}"
                                >
                                    <x-ri-pass-pending-fill :size="24" />
                                    <span class="ficha-empleados-filters__pending-count">{{ number_format($pendingCount) }}</span>
                                </a>
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
                                    <th>Cedula</th>
                                    <th>Nombre completo</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Fecha contratacion</th>
                                    @if ($currentEstado === 'en_ficha')
                                        <th>Agregado a ficha</th>
                                        <th>Agregado por</th>
                                    @else
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($entries as $entry)
                                    @php
                                        $fichaHref = $canManage
                                            ? route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry)
                                            : null;
                                    @endphp
                                    <tr
                                        @if ($fichaHref)
                                            class="ficha-empleados-row--clickable"
                                            data-ficha-href="{{ $fichaHref }}"
                                            tabindex="0"
                                            role="link"
                                            aria-label="Ver ficha de {{ $entry->hired_full_name }}"
                                        @endif
                                    >
                                        <td>{{ $entry->hired_document }}</td>
                                        <td>{{ $entry->hired_full_name }}</td>
                                        <td>{{ $entry->positionName() ?: '—' }}</td>
                                        <td>{{ $entry->clientName() ?: '—' }}</td>
                                        <td>{{ $entry->cityName() ?: '—' }}</td>
                                        <td><x-date-table :value="$entry->requisition?->hiring_date" /></td>
                                        @if ($entry->moved_to_ficha_at !== null)
                                            <td><x-date-table :value="$entry->moved_to_ficha_at" datetime /></td>
                                            <td>{{ $entry->movedBy?->name ?: '—' }}</td>
                                        @else
                                            <td class="table-actions ficha-empleados-row__actions">
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
                                        <td colspan="{{ $currentEstado === 'en_ficha' ? 8 : 7 }}">No hay registros para este filtro.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($currentEstado === 'en_ficha')
                @include('areas.gestion_humana.ficha-empleados.partials.masivos-modal', [
                    'filters' => $filters,
                    'canManage' => $canManage,
                    'show' => $errors->has('export') || $errors->has('import_file') || (is_array(session('import_result')) && (session('import_result.failed') ?? 0) > 0),
                ])
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.ficha-empleados-page__table-wrap tbody').forEach(function (tbody) {
                    tbody.addEventListener('click', function (event) {
                        if (event.target.closest('button, a, form, input, label, select, textarea')) {
                            return;
                        }

                        var row = event.target.closest('tr[data-ficha-href]');
                        if (!row) {
                            return;
                        }

                        window.location.assign(row.dataset.fichaHref);
                    });

                    tbody.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter' && event.key !== ' ') {
                            return;
                        }

                        var row = event.target.closest('tr[data-ficha-href]');
                        if (!row) {
                            return;
                        }

                        event.preventDefault();
                        window.location.assign(row.dataset.fichaHref);
                    });
                });

                document.querySelectorAll('[data-ficha-import-file]').forEach(function (input) {
                    var form = input.closest('[data-ficha-import-form]');
                    var nameEl = form?.querySelector('[data-ficha-import-name]');
                    var submitBtn = form?.querySelector('[data-ficha-import-submit]');
                    var chooseBtn = form?.querySelector('[data-ficha-import-choose]');
                    var loadingEl = document.querySelector('[data-ficha-import-loading]');

                    if (!nameEl || !submitBtn || !form) {
                        return;
                    }

                    input.addEventListener('change', function () {
                        var file = input.files && input.files[0];
                        nameEl.textContent = file ? file.name : 'Sin archivo seleccionado';
                        submitBtn.disabled = !file;
                    });

                    form.addEventListener('submit', function () {
                        if (loadingEl) {
                            loadingEl.hidden = false;
                        }

                        submitBtn.disabled = true;

                        if (chooseBtn) {
                            chooseBtn.classList.add('is-disabled');
                            chooseBtn.style.pointerEvents = 'none';
                        }

                        submitBtn.innerHTML = '<span class="ficha-empleados-masivos-modal__btn-spinner" aria-hidden="true"></span> Importando…';
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
