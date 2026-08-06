<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.archivo.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Historias Laborales</h2>
                <p class="panel-text">Gestion humana — ubicacion documental de empleados (estantes y cajas)</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section archivo-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert {{ ($importHasFailures ?? false) ? 'alert--warning' : 'alert--success' }} archivo-page__alert">
                    {{ session('status') }}
                </div>
            @endif

            @include('partials.import-failure-report', [
                'importResult' => $importResult ?? session('import_result'),
                'downloadRoute' => 'gestion-humana.archivo.import-report',
            ])

            @if ($errors->has('import_file'))
                <div class="alert alert--danger archivo-page__alert">{{ $errors->first('import_file') }}</div>
            @endif

            @if ($errors->any() && ! $errors->has('import_file'))
                <div class="alert alert--danger archivo-page__alert">
                    @foreach ($errors->all() as $error)
                        <p class="archivo-page__inline-error">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="panel">
                <div class="panel__body panel__body--compact">
                    <div class="req-manage-filters">
                        <div class="req-manage-filters__head">
                            <div class="panel-heading-row panel-heading-row--wrap">
                                <h3 class="panel-title">Empleados en ficha</h3>
                                <p class="panel-text">Cedula, nombre o codigo de requisicion</p>
                            </div>
                            <div class="req-manage-filters__actions">
                                <button
                                    type="button"
                                    class="btn btn--secondary btn--sm"
                                    title="Consulta multiple por cedulas"
                                    aria-label="Consulta multiple por cedulas"
                                    x-data=""
                                    x-on:click.prevent="$dispatch('open-modal', 'archivo-consult')"
                                >
                                    <x-lucide-icon name="search" :size="16" />
                                    Consulta multiple
                                </button>
                                @if ($canExportArchive ?? false)
                                    <x-export-excel
                                        route="{{ route('gestion-humana.ficha-empleados.employees.export-archive-template', request()->query()) }}"
                                        label="Exportar archivo"
                                        class="btn btn--secondary btn--sm"
                                    />
                                @endif
                                @if ($canManage)
                                    <button
                                        type="button"
                                        class="btn btn--secondary btn--sm"
                                        title="Importar estantes y cajas"
                                        aria-label="Importar estantes y cajas"
                                        x-data=""
                                        x-on:click.prevent="$dispatch('open-modal', 'archivo-import')"
                                    >
                                        <x-lucide-icon name="upload" :size="16" />
                                        Importar
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="req-manage-filters__toolbar">
                            <form method="GET" class="req-manage-filters__search-col">
                                @if ($filters['consultation'] ?? null)
                                    <input type="hidden" name="consultation" value="{{ $filters['consultation'] }}">
                                @endif
                                <label class="req-manage-filters__label" for="archivo-search-input">Buscar</label>
                                <div class="req-manage-filters__search-group">
                                    <input
                                        id="archivo-search-input"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Cedula, nombre o codigo de requisicion"
                                    >
                                    <button type="submit" class="btn btn--primary btn--sm">Buscar</button>
                                </div>
                            </form>
                        </div>

                        <p class="req-manage-filters__meta">
                            <strong>{{ number_format($entries->count()) }}</strong>
                            {{ $entries->count() === 1 ? 'empleado' : 'empleados' }}
                            @if ($filters['q'] ?? '')
                                · Busqueda: <strong>{{ $filters['q'] }}</strong>
                            @endif
                            @if ($activeConsultation ?? null)
                                · Consulta #{{ $activeConsultation->id }}
                            @endif
                        </p>
                    </div>

                    @if ($activeConsultation ?? null)
                        <div class="archivo-consult-banner">
                            <div class="archivo-consult-banner__body">
                                <p class="archivo-consult-banner__title">Consulta activa #{{ $activeConsultation->id }}</p>
                                <p class="archivo-consult-banner__meta">
                                    {{ $activeConsultation->created_at?->format('d/m/Y H:i') }}
                                    · {{ $activeConsultation->user?->name ?: 'Usuario' }}
                                    · {{ $activeConsultation->documents_matched }}/{{ $activeConsultation->documents_requested }} cedula(s) en ficha
                                </p>
                                <p class="archivo-consult-banner__types">
                                    <strong>Motivos:</strong> {{ implode(' · ', $activeConsultation->typeLabels()) }}
                                </p>
                                @if ($activeConsultation->delivered_to)
                                    <p class="archivo-consult-banner__docs">
                                        <strong>Entregada a:</strong> {{ $activeConsultation->delivered_to }}
                                    </p>
                                @endif
                                <p class="archivo-consult-banner__docs">
                                    <strong>Cedulas:</strong> {{ implode(', ', $activeConsultation->document_numbers ?? []) }}
                                </p>
                                @if (! empty($activeConsultation->documents_not_found))
                                    <p class="archivo-consult-banner__missing">
                                        <strong>No encontradas:</strong> {{ implode(', ', $activeConsultation->documents_not_found) }}
                                    </p>
                                @endif
                            </div>
                            <div class="archivo-consult-banner__actions">
                                <a href="{{ route('gestion-humana.archivo.consultation-history.index') }}" class="btn btn--secondary btn--sm">
                                    Ver historial
                                </a>
                                <a href="{{ route('gestion-humana.archivo.labor-histories.index', array_filter(['q' => $filters['q'] ?? null])) }}" class="btn btn--secondary btn--sm">
                                    Quitar filtro
                                </a>
                            </div>
                        </div>
                    @endif

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable" data-dt-responsive="false" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Cedula</th>
                                    <th>Nombre</th>
                                    <th>Cargo</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Fecha ingreso</th>
                                    <th>Fecha retiro</th>
                                    <th>Estante</th>
                                    <th>Caja</th>
                                    @if ($canManage)
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($entries as $entry)
                                    @php
                                        $rowFormId = 'archivo-row-'.$entry->id;
                                    @endphp
                                    <tr>
                                        <td>{{ $entry->profile?->document_number ?: $entry->hired_document }}</td>
                                        <td>{{ $entry->profile?->full_name ?: $entry->hired_full_name }}</td>
                                        <td>{{ $entry->positionName() ?: '—' }}</td>
                                        <td>{{ $entry->clientName() ?: '—' }}</td>
                                        <td>{{ $entry->cityName() ?: '—' }}</td>
                                        <td><x-date-table :value="$entry->hireDate()" /></td>
                                        <td><x-date-table :value="$entry->terminationDate()" /></td>
                                        @if ($canManage)
                                            <td class="archivo-page__field-cell">
                                                <label class="sr-only" for="{{ $rowFormId }}-shelf">Estante</label>
                                                <input
                                                    id="{{ $rowFormId }}-shelf"
                                                    form="{{ $rowFormId }}"
                                                    type="text"
                                                    name="archive_shelf"
                                                    class="form-input archivo-page__inline-input"
                                                    maxlength="100"
                                                    value="{{ old('archive_shelf', $entry->profile?->archive_shelf) }}"
                                                    placeholder="Estante"
                                                >
                                            </td>
                                            <td class="archivo-page__field-cell">
                                                <label class="sr-only" for="{{ $rowFormId }}-box">Caja</label>
                                                <input
                                                    id="{{ $rowFormId }}-box"
                                                    form="{{ $rowFormId }}"
                                                    type="text"
                                                    name="archive_box"
                                                    class="form-input archivo-page__inline-input"
                                                    maxlength="100"
                                                    value="{{ old('archive_box', $entry->profile?->archive_box) }}"
                                                    placeholder="Caja"
                                                >
                                            </td>
                                            <td class="table-actions archivo-page__actions-cell">
                                                <button type="submit" form="{{ $rowFormId }}" class="btn btn--primary btn--sm">
                                                    Actualizar
                                                </button>
                                            </td>
                                        @else
                                            <td>{{ $entry->profile?->archive_shelf ?: '—' }}</td>
                                            <td>{{ $entry->profile?->archive_box ?: '—' }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManage ? 10 : 9 }}">No hay empleados en ficha.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($canManage)
                        @foreach ($entries as $entry)
                            <form
                                id="archivo-row-{{ $entry->id }}"
                                method="POST"
                                action="{{ route('gestion-humana.archivo.update', $entry) }}"
                                class="archivo-page__row-form"
                                hidden
                            >
                                @csrf
                                @method('PATCH')
                                @if ($filters['q'] ?? '')
                                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                @endif
                                @if ($filters['consultation'] ?? null)
                                    <input type="hidden" name="consultation" value="{{ $filters['consultation'] }}">
                                @endif
                            </form>
                        @endforeach
                    @endif
                </div>
            </div>

            @include('areas.gestion_humana.archivo.partials.consult-modal', [
                'consultationTypes' => $consultationTypes ?? config('employee_ficha.archive_consultation_types', []),
                'show' => $errors->has('documents') || $errors->has('consultation_types') || $errors->has('consultation_types.*'),
            ])

            @if ($canManage)
                @include('areas.gestion_humana.archivo.partials.import-modal', [
                    'canManage' => $canManage,
                    'canExportArchive' => $canExportArchive ?? false,
                    'show' => $errors->has('import_file') || (is_array(session('import_result')) && ((session('import_result.failures_count') ?? 0) > 0 || session('import_result.report_token'))),
                ])
            @endif
        </div>
    </div>

    @if ($canManage)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var fileInput = document.querySelector('[data-archivo-import-file]');
                    var fileName = document.querySelector('[data-archivo-import-name]');
                    var submitBtn = document.querySelector('[data-archivo-import-submit]');
                    var form = document.querySelector('[data-archivo-import-form]');
                    var loading = document.querySelector('[data-archivo-import-loading]');

                    if (fileInput && fileName && submitBtn) {
                        fileInput.addEventListener('change', function () {
                            var name = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                            fileName.textContent = name ? name.name : 'Sin archivo seleccionado';
                            submitBtn.disabled = !name;
                        });
                    }

                    if (form && loading && submitBtn) {
                        form.addEventListener('submit', function () {
                            loading.hidden = false;
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="ficha-empleados-masivos-modal__btn-spinner" aria-hidden="true"></span> Importando…';
                        });
                    }
                });
            </script>
        @endpush
    @endif
</x-app-layout>
