<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 class="panel-title">Solicitud de compra {{ $purchaseRequest->folio() }}</h3>
                            <p class="panel-text">
                                Estado:
                                @php
                                    $estadoPill = match ($purchaseRequest->estado) {
                                        'aprobado' => 'status-pill--success',
                                        'rechazado' => 'status-pill--danger',
                                        default => 'status-pill--info',
                                    };
                                    $estadoLabel = match ($purchaseRequest->estado) {
                                        'aprobado' => 'Aprobado',
                                        'rechazado' => 'Rechazado',
                                        default => 'Pendiente',
                                    };
                                @endphp
                                <span class="status-pill {{ $estadoPill }}">{{ $estadoLabel }}</span>
                                @if ($purchaseRequest->urgente)
                                    <span class="status-pill status-pill--warning" style="margin-left: 0.35rem;">Urgente</span>
                                @endif
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <a href="{{ route('purchase-requests.export.pdf', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}" class="btn btn--secondary btn--sm">
                                Descargar PDF
                            </a>
                            <x-export-excel
                                route="{{ route('purchase-requests.export.excel', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                                label="Exportar Excel"
                                class="btn btn--secondary btn--sm"
                            />
                            @can('approve', $purchaseRequest)
                                <a href="{{ route('purchase-requests.approval.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">
                                    Volver a pendientes
                                </a>
                            @else
                                <a href="{{ route('purchase-requests.index', ['module' => $module]) }}" class="btn btn--secondary btn--sm">
                                    Volver al listado
                                </a>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="panel__body">
                    <div class="dashboard-stat-grid bottom-spaced">
                        <article class="card card--muted">
                            <p class="text-caption">Fecha de solicitud</p>
                            <p class="panel-title">{{ optional($purchaseRequest->fecha_solicitud)->format('d/m/Y') ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Solicitante</p>
                            <p class="panel-title">{{ $purchaseRequest->user?->name ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Area</p>
                            <p class="panel-title">{{ $purchaseRequest->areaLabel() ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Solicitud para</p>
                            <p class="panel-title">{{ $purchaseRequest->solicitud_para }}</p>
                        </article>
                    </div>

                    <div class="dashboard-stat-grid bottom-spaced">
                        <article class="card card--muted">
                            <p class="text-caption">Director aprobador</p>
                            <p class="panel-title">{{ $purchaseRequest->aprobador?->name ?? '—' }}</p>
                        </article>
                        <article class="card card--muted">
                            <p class="text-caption">Fecha de aprobacion</p>
                            <p class="panel-title">{{ optional($purchaseRequest->fecha_aprobacion)->format('d/m/Y') ?? '—' }}</p>
                        </article>
                        @if ($purchaseRequest->estado_compras)
                            <article class="card card--muted">
                                <p class="text-caption">Estado compras</p>
                                <p class="panel-title">{{ $purchaseRequest->estadoComprasLabel() }}</p>
                            </article>
                        @endif
                    </div>

                    @if ($purchaseRequest->solicitud_para === 'Cliente')
                        <div class="card card--info block-spaced">
                            <p class="text-caption">Datos del cliente</p>
                            <p><strong>Razon social:</strong> {{ $purchaseRequest->razon_social ?? '—' }}</p>
                            <p><strong>Proyecto nuevo:</strong> {{ $purchaseRequest->proyecto_nuevo ? 'Si' : 'No' }}</p>
                            <p><strong>Asume el cliente:</strong> {{ $purchaseRequest->asume_cliente ? 'Si' : 'No' }}</p>
                        </div>
                    @endif

                    @if ($purchaseRequest->comentarios_director)
                        <div class="card card--muted block-spaced">
                            <p class="text-caption">Comentarios del director</p>
                            <p>{{ $purchaseRequest->comentarios_director }}</p>
                        </div>
                    @endif

                    @if ($purchaseRequest->descripcion)
                        <div class="block-spaced">
                            <h4 class="form-label">Descripcion general</h4>
                            <p class="text-muted">{{ $purchaseRequest->descripcion }}</p>
                        </div>
                    @endif

                    @if ($purchaseRequest->justificacion)
                        <div class="block-spaced">
                            <h4 class="form-label">Justificacion</h4>
                            <p class="text-muted">{{ $purchaseRequest->justificacion }}</p>
                        </div>
                    @endif

                    <div class="block-spaced">
                        <table class="supply-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Foto</th>
                                    <th>Cantidad</th>
                                    <th>Descripcion</th>
                                    <th>Referencia</th>
                                    <th>Utilizacion</th>
                                    <th>Ubicacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseRequest->items as $item)
                                    <tr>
                                        <td class="text-center">{{ $item->orden ?? $loop->iteration }}</td>
                                        <td class="text-center">
                                            @if ($item->foto_path)
                                                <a href="{{ Storage::disk('public')->url($item->foto_path) }}" target="_blank" rel="noopener">
                                                    <img
                                                        src="{{ Storage::disk('public')->url($item->foto_path) }}"
                                                        alt="Foto {{ $item->descripcion }}"
                                                        style="max-width: 64px; max-height: 48px; object-fit: contain; border-radius: 4px;"
                                                    >
                                                </a>
                                            @else
                                                <span class="text-muted text-small">Sin foto</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $item->cantidad }}</td>
                                        <td style="font-weight: 600; color: var(--color-primary);">{{ $item->descripcion }}</td>
                                        <td>{{ $item->referencia }}</td>
                                        <td>{{ $item->utilizacion }}</td>
                                        <td>{{ $item->ubicacion }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('modules.purchase-requests.partials.approval-form')

                    <div class="block-spaced-lg">
                        <h4 class="form-label">Registro de correos de esta solicitud</h4>
                        @if ($purchaseRequest->mailLogs->isEmpty())
                            <p class="text-muted text-small">No hay correos registrados para esta solicitud.</p>
                        @else
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>Fecha / hora</th>
                                        <th>Tipo</th>
                                        <th>Destinatario</th>
                                        <th>Estado</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchaseRequest->mailLogs as $mailLog)
                                        <tr>
                                            <td>{{ optional($mailLog->sent_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                            <td>{{ $mailLog->typeLabel() }}</td>
                                            <td>{{ $mailLog->recipient_email }}</td>
                                            <td>
                                                @php
                                                    $mailStatusClass = $mailLog->status === \App\Models\PurchaseRequestMailLog::STATUS_ENVIADO
                                                        ? 'status-pill--success'
                                                        : 'status-pill--danger';
                                                @endphp
                                                <span class="status-pill {{ $mailStatusClass }}">{{ $mailLog->statusLabel() }}</span>
                                            </td>
                                            <td>{{ $mailLog->detail ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                    @if ($purchaseRequest->comentarios_compras)
                        <div class="block-spaced-lg">
                            <h4 class="form-label">Comentarios de compras</h4>
                            <p class="text-muted">{{ $purchaseRequest->comentarios_compras }}</p>
                            @if ($purchaseRequest->procesadoComprasPor)
                                <p class="text-small text-muted" style="margin-top: 0.5rem;">
                                    Procesado por: {{ $purchaseRequest->procesadoComprasPor->name }}
                                    @if ($purchaseRequest->procesado_compras_at)
                                        el {{ $purchaseRequest->procesado_compras_at->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
