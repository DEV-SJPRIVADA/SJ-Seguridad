<x-app-layout>
    <x-slot name="header">
        <div class="app-container comercial-checklist-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Checklist documental</h2>
                <p class="panel-text">Comercial — matriz por cliente (NIT)</p>
            </div>
        </div>
    </x-slot>

    @php
        $hasActiveFilters = ($filters['q'] ?? '') !== ''
            || ($filters['city'] ?? '') !== ''
            || ($filters['doc_vigencia'] ?? '') !== '';
    @endphp

    <div class="page-section comercial-checklist-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success comercial-checklist-page__alert">{{ session('status') }}</div>
            @endif

            <div class="panel comercial-checklist-panel">
                <div class="panel__body panel__body--compact">
                    <div class="req-manage-filters comercial-checklist-filters">
                        <div class="req-manage-filters__head">
                            <div class="panel-heading-row panel-heading-row--wrap">
                                <h3 class="panel-title">Matriz documental</h3>
                                <p class="panel-text">Una fila por cliente · columnas por documento</p>
                            </div>
                            <div class="req-manage-filters__actions comercial-checklist-filters__actions">
                                <a href="{{ route('comercial.matriz.clients.index') }}" class="btn btn--secondary btn--sm">Volver a clientes</a>
                                <x-export-excel route="{{ route('comercial.matriz.clients.checklist.export', request()->query()) }}" />
                                @if ($hasActiveFilters)
                                    <a href="{{ route('comercial.matriz.clients.checklist.index') }}" class="btn btn--secondary btn--sm">Limpiar filtros</a>
                                @endif
                            </div>
                        </div>

                        <form method="GET" class="comercial-checklist-filters__form">
                            <label class="req-manage-filters__label" for="checklist-search-input">Buscar</label>
                            <div class="req-manage-filters__search-group comercial-checklist-filters__search-group">
                                <input
                                    id="checklist-search-input"
                                    type="search"
                                    name="q"
                                    class="form-input"
                                    value="{{ $filters['q'] }}"
                                    placeholder="NIT, nombre o representante"
                                >
                                <input
                                    id="checklist-city-input"
                                    type="search"
                                    name="city"
                                    class="form-input comercial-checklist-filters__city"
                                    value="{{ $filters['city'] }}"
                                    placeholder="Ciudad"
                                >
                                <select id="checklist-doc-vigencia" name="doc_vigencia" class="form-select comercial-checklist-filters__vigencia">
                                    <option value="">Toda documentación</option>
                                    <option value="expiring" @selected($filters['doc_vigencia'] === 'expiring')>Por vencer</option>
                                    <option value="expired" @selected($filters['doc_vigencia'] === 'expired')>Vencida</option>
                                </select>
                                <button type="submit" class="btn btn--primary">Buscar</button>
                            </div>
                        </form>

                        <p class="req-manage-filters__meta comercial-checklist-filters__meta">
                            <strong>{{ number_format($clients->count()) }}</strong>
                            {{ $clients->count() === 1 ? 'cliente' : 'clientes' }}
                            @if ($filters['doc_vigencia'] === 'expiring')
                                · Documentación <strong>por vencer</strong>
                            @elseif ($filters['doc_vigencia'] === 'expired')
                                · Documentación <strong>vencida</strong>
                            @endif
                        </p>
                    </div>

                    @if ($canManage)
                        @foreach ($clients as $client)
                            <form
                                id="checklist-form-{{ $client->id }}"
                                method="POST"
                                action="{{ route('comercial.matriz.clients.checklist.update', $client) }}"
                                class="comercial-checklist-page__patch-form"
                            >
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                <input type="hidden" name="city" value="{{ $filters['city'] }}">
                                <input type="hidden" name="doc_vigencia" value="{{ $filters['doc_vigencia'] }}">
                            </form>
                        @endforeach
                    @endif

                    <div class="data-table-wrap comercial-checklist-page__table-wrap">
                        <table class="data-table js-datatable comercial-checklist-table" style="width:100%; min-width:1100px;">
                            <thead>
                                <tr>
                                    <th>NIT</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    @foreach ($documentFields as $label)
                                        <th class="comercial-checklist-table__doc-th">{{ $label }}</th>
                                    @endforeach
                                    <th>Vencimiento</th>
                                    <th title="Días de anticipación">Días</th>
                                    @if ($canManage)
                                        <th>Acción</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clients as $client)
                                    @php
                                        $itemsByKey = $client->documentItems->keyBy('document_key');
                                        $formId = 'checklist-form-'.$client->id;
                                        $docLabel = $client->documentationVigenciaLabel();
                                    @endphp
                                    <tr>
                                        <td>{{ $client->nit }}</td>
                                        <td>
                                            <div class="comercial-checklist-table__client-name">{{ $client->name }}</div>
                                            @if ($docLabel)
                                                <span class="status-pill {{ $docLabel === 'Doc. vencida' ? 'status-pill--danger' : 'status-pill--warning' }} comercial-checklist-table__doc-badge">{{ $docLabel }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $client->city ?: '—' }}</td>
                                        @foreach ($documentFields as $documentKey => $label)
                                            @php
                                                $docStatus = $itemsByKey->get($documentKey)?->status ?? '';
                                            @endphp
                                            <td class="comercial-checklist-table__doc-cell">
                                                @if ($canManage)
                                                    <select
                                                        name="documents[{{ $documentKey }}]"
                                                        form="{{ $formId }}"
                                                        class="form-select checklist-doc-select checklist-doc-select--{{ $docStatus !== '' ? $docStatus : 'empty' }}"
                                                        data-checklist-doc-select
                                                    >
                                                        <option value="">—</option>
                                                        @foreach ($documentStatuses as $statusKey => $statusLabel)
                                                            <option value="{{ $statusKey }}" @selected($docStatus === $statusKey)>{{ $statusLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    @if ($docStatus === '')
                                                        —
                                                    @else
                                                        <span class="checklist-doc-pill checklist-doc-pill--{{ $docStatus }}">{{ \App\Support\CommercialDocumentCatalog::statusLabel($docStatus) }}</span>
                                                    @endif
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>
                                            @if ($canManage)
                                                <input
                                                    type="date"
                                                    name="documentation_expires_on"
                                                    form="{{ $formId }}"
                                                    class="form-input comercial-checklist-table__date-input"
                                                    value="{{ optional($client->documentation_expires_on)->format('Y-m-d') }}"
                                                >
                                            @else
                                                <x-date-table :value="$client->documentation_expires_on" />
                                            @endif
                                        </td>
                                        <td>
                                            @if ($canManage)
                                                <input
                                                    type="number"
                                                    name="alert_days_before"
                                                    form="{{ $formId }}"
                                                    class="form-input comercial-checklist-table__days-input"
                                                    min="0"
                                                    max="3650"
                                                    value="{{ $client->alert_days_before ?? 30 }}"
                                                >
                                            @else
                                                {{ $client->alert_days_before ?? 30 }}
                                            @endif
                                        </td>
                                        @if ($canManage)
                                            <td class="table-actions">
                                                <button type="submit" form="{{ $formId }}" class="btn btn--primary btn--sm">Guardar</button>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 5 + count($documentFields) + ($canManage ? 1 : 0) }}">No hay clientes que coincidan con los filtros.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-checklist-doc-select]').forEach(function (select) {
                select.addEventListener('change', function () {
                    var value = select.value || 'empty';
                    select.className = 'form-select checklist-doc-select checklist-doc-select--' + value;
                });
            });
        </script>
    @endpush
</x-app-layout>
