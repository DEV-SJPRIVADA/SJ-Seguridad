<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="page-header-inner" style="padding-top: 0; margin-bottom: 1.25rem;">
                <h2 class="page-title">Editar {{ $requisition->code }}</h2>
                <p class="page-subtitle">Gestion humana puede ajustar datos operativos, registrar compensacion, cambiar el estado y dejar trazabilidad en el historial.</p>
            </div>

            <div class="req-form-layout">
                <div class="req-form-layout__main">
                    <form method="POST" action="{{ route('requisitions.update', ['module' => $moduleKey, 'requisition' => $requisition]) }}" class="form-stack">
                        @csrf
                        @method('PATCH')

                        @include('modules.requisitions.partials.form-fields', [
                            'moduleLabel' => $moduleLabel,
                            'requisition' => $requisition,
                            'showHumanResourcesFields' => true,
                            'areaOptions' => $areaOptions,
                            'catalogs' => $catalogs,
                            'sexOptions' => $sexOptions,
                            'statusLabels' => $statusLabels,
                            'clientSearchUrl' => $clientSearchUrl,
                            'selectedCommercialClient' => $selectedCommercialClient,
                        ])

                        <div class="req-form-actions">
                            <p class="req-form-actions__note">Los cambios de estado y de datos quedan registrados en el historial operativo con fecha, usuario y detalle.</p>
                            <div class="req-form-actions__group">
                                <a href="{{ route('requisitions.manage', ['module' => $moduleKey]) }}" class="btn btn--secondary">Volver</a>
                                <x-primary-button>Guardar cambios</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="req-form-aside">

                <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Historial de estados</h3>
                            <p class="panel-text">Traza de cambios y responsable del movimiento.</p>
                        </div>
                        <div class="panel__body">
                            @if ($requisition->statusLogs->isEmpty())
                                <p class="panel-text" style="margin: 0;">Sin cambios de estado registrados.</p>
                            @else
                                <ul class="req-form-history">
                                    @foreach ($requisition->statusLogs->sortByDesc('created_at') as $log)
                                        <li class="req-form-history__item">
                                            <p class="req-form-history__date">{{ $log->created_at?->format('Y-m-d H:i') }}</p>
                                            <div class="req-form-history__change">
                                                <span class="status-pill status-pill--req-{{ $log->from_status }}">
                                                    {{ $statusLabels[$log->from_status] ?? 'Inicial' }}
                                                </span>
                                                <span class="req-form-history__arrow" aria-hidden="true">→</span>
                                                <span class="status-pill status-pill--req-{{ $log->to_status }}">
                                                    {{ $statusLabels[$log->to_status] ?? $log->to_status }}
                                                </span>
                                            </div>
                                            <p class="req-form-history__author">{{ $log->author?->name ?? 'Sistema' }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Historial de cambios</h3>
                            <p class="panel-text">Trazabilidad de modificaciones en campos de la requisicion.</p>
                        </div>
                        <div class="panel__body">
                            @php
                                $changeBatches = $requisition->changeLogs
                                    ->sortByDesc('created_at')
                                    ->groupBy('change_batch');
                            @endphp

                            @if ($changeBatches->isEmpty())
                                <p class="panel-text" style="margin: 0;">Sin cambios registrados en edicion.</p>
                            @else
                                <ul class="req-form-history req-form-history--scrollable">
                                    @foreach ($changeBatches as $batchLogs)
                                        @php
                                            $firstLog = $batchLogs->first();
                                        @endphp
                                        <li class="req-form-history__item">
                                            <p class="req-form-history__date">{{ $firstLog->created_at?->format('Y-m-d H:i') }}</p>
                                            <ul class="req-form-change-log">
                                                @foreach ($batchLogs as $log)
                                                    <li class="req-form-change-log__item">
                                                        <span class="req-form-change-log__field">{{ $log->field_label }}</span>
                                                        <span class="req-form-change-log__values">
                                                            <span class="req-form-change-log__value req-form-change-log__value--old">{{ $log->old_value ?? '—' }}</span>
                                                            <span class="req-form-history__arrow" aria-hidden="true">→</span>
                                                            <span class="req-form-change-log__value req-form-change-log__value--new">{{ $log->new_value ?? '—' }}</span>
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <p class="req-form-history__author">{{ $firstLog->author?->name ?? 'Sistema' }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de guardar</h3>
                            <p class="panel-text">Verifica coherencia entre motivo, cliente y compensacion.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="req-form-guide__list">
                                <li class="req-form-guide__item">Confirma que el estado refleje el avance real del proceso.</li>
                                <li class="req-form-guide__item">Asigna reclutador cuando la solicitud entre en gestion activa.</li>
                                <li class="req-form-guide__item">Valida la matriz de compensacion antes de contratacion.</li>
                                <li class="req-form-guide__item">Usa observaciones de GH para contexto visible en seguimiento.</li>
                                <li class="req-form-guide__item">Cliente externo debe coincidir con la matriz comercial.</li>
                            </ul>
                        </div>
                    </div>


                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
