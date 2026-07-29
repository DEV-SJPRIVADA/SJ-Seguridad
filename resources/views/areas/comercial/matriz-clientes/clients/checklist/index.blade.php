<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <h2 class="panel-title" style="margin:0;">Checklist documental</h2>
            <p class="panel-text" style="margin:0.25rem 0 0;">Comercial — matriz por cliente (NIT)</p>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
            @endif

            <div class="panel">
                <div class="panel__header" style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <h3 class="panel-title">Matriz documental</h3>
                        <p class="panel-text">Una fila por cliente; columnas por documento. Vencimiento y dias al final.</p>
                    </div>
                    <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                        <a href="{{ route('comercial.matriz.clients.index') }}" class="btn btn--secondary">Volver a clientes</a>
                        <x-export-excel route="{{ route('comercial.matriz.clients.checklist.export', request()->query()) }}" />
                    </div>
                </div>

                <div class="panel__body">
                    <form method="GET" class="permission-filter-bar bottom-spaced">
                        <input type="search" name="q" class="form-input permission-filter-bar__search" value="{{ $filters['q'] }}" placeholder="NIT, nombre o representante">
                        <input type="search" name="city" class="form-input permission-filter-bar__select" value="{{ $filters['city'] }}" placeholder="Ciudad">
                        <select name="doc_vigencia" class="form-select permission-filter-bar__select">
                            <option value="">Documentacion: todas</option>
                            <option value="expiring" @selected($filters['doc_vigencia'] === 'expiring')>Por vencer</option>
                            <option value="expired" @selected($filters['doc_vigencia'] === 'expired')>Vencida</option>
                        </select>
                        <button type="submit" class="btn btn--secondary">Filtrar</button>
                    </form>

                    @if ($canManage)
                        @foreach ($clients as $client)
                            <form
                                id="checklist-form-{{ $client->id }}"
                                method="POST"
                                action="{{ route('comercial.matriz.clients.checklist.update', $client) }}"
                                style="display:none;"
                            >
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                                <input type="hidden" name="city" value="{{ $filters['city'] }}">
                                <input type="hidden" name="doc_vigencia" value="{{ $filters['doc_vigencia'] }}">
                            </form>
                        @endforeach
                    @endif

                    <div class="data-table-wrap" style="overflow-x:auto;">
                        <table class="data-table js-datatable" style="width:100%; min-width:1100px;">
                            <thead>
                                <tr>
                                    <th style="min-width:7rem;">NIT</th>
                                    <th style="min-width:10rem;">Cliente</th>
                                    <th style="min-width:6rem;">Ciudad</th>
                                    @foreach ($documentFields as $label)
                                        <th style="min-width:5.5rem; font-size:0.75rem;">{{ $label }}</th>
                                    @endforeach
                                    <th style="min-width:8.5rem;">Vencimiento</th>
                                    <th style="min-width:4.5rem;" title="Dias de anticipacion">Dias</th>
                                    @if ($canManage)
                                        <th style="min-width:5rem;">Accion</th>
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
                                            <div>{{ $client->name }}</div>
                                            @if ($docLabel)
                                                <span class="status-pill {{ $docLabel === 'Doc. vencida' ? 'status-pill--danger' : 'status-pill--warning' }}" style="font-size:0.7rem; margin-top:0.25rem;">{{ $docLabel }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $client->city ?: '—' }}</td>
                                        @foreach ($documentFields as $documentKey => $label)
                                            <td>
                                                @if ($canManage)
                                                    <select
                                                        name="documents[{{ $documentKey }}]"
                                                        form="{{ $formId }}"
                                                        class="form-select"
                                                        style="font-size:0.75rem; padding:0.25rem 0.35rem; min-width:4.5rem;"
                                                    >
                                                        <option value="">—</option>
                                                        @foreach ($documentStatuses as $statusKey => $statusLabel)
                                                            <option value="{{ $statusKey }}" @selected(($itemsByKey->get($documentKey)?->status) === $statusKey)>{{ $statusLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    {{ \App\Support\CommercialDocumentCatalog::statusLabel($itemsByKey->get($documentKey)?->status) }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>
                                            @if ($canManage)
                                                <input
                                                    type="date"
                                                    name="documentation_expires_on"
                                                    form="{{ $formId }}"
                                                    class="form-input"
                                                    style="font-size:0.8rem; padding:0.25rem 0.35rem; min-width:8rem;"
                                                    value="{{ optional($client->documentation_expires_on)->format('Y-m-d') }}"
                                                >
                                            @else
                                                {{ optional($client->documentation_expires_on)->format('Y-m-d') ?: '—' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($canManage)
                                                <input
                                                    type="number"
                                                    name="alert_days_before"
                                                    form="{{ $formId }}"
                                                    class="form-input"
                                                    min="0"
                                                    max="3650"
                                                    style="font-size:0.8rem; padding:0.25rem 0.35rem; width:4rem;"
                                                    value="{{ $client->alert_days_before ?? 30 }}"
                                                >
                                            @else
                                                {{ $client->alert_days_before ?? 30 }}
                                            @endif
                                        </td>
                                        @if ($canManage)
                                            <td>
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
</x-app-layout>
