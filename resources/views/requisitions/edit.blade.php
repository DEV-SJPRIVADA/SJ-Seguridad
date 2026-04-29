<x-app-layout>
    <x-slot name="header">
        @include('requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="form-layout bottom-spaced">
                <section class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Editar {{ $requisition->code }}</h3>
                        <p class="panel-text">Gestion humana puede ajustar datos, registrar observaciones y cambiar el estado.</p>
                    </div>

                    <form method="POST" action="{{ route('requisitions.update', ['module' => $moduleKey, 'requisition' => $requisition]) }}" class="panel__body form-stack">
                        @csrf
                        @method('PATCH')

                        @include('requisitions.partials.form-fields', [
                            'moduleLabel' => $moduleLabel,
                            'requisition' => $requisition,
                            'showHumanResourcesFields' => true,
                            'areaOptions' => $areaOptions,
                            'catalogs' => $catalogs,
                            'sexOptions' => $sexOptions,
                            'statusLabels' => $statusLabels,
                        ])

                        <div class="form-actions">
                            <p class="text-small text-muted">Cada cambio de estado queda registrado en el historial operativo.</p>
                            <div class="form-actions__group">
                                <a href="{{ route('requisitions.manage', ['module' => $moduleKey]) }}" class="btn btn--secondary">Volver</a>
                                <x-primary-button>Guardar cambios</x-primary-button>
                            </div>
                        </div>
                    </form>
                </section>

                <aside class="panel">
                    <div class="panel__header">
                        <h3 class="panel-title">Historial de estados</h3>
                        <p class="panel-text">Traza de cambios y responsable del movimiento.</p>
                    </div>

                    <div class="panel__body">
                        <div class="data-table-wrap">
                            <table class="data-table js-datatable">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cambio</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($requisition->statusLogs->sortByDesc('created_at') as $log)
                                        <tr>
                                            <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                                <td>
                                                    <div class="status-pill status-pill--req-{{ $log->from_status }}">
                                                        {{ $statusLabels[$log->from_status] ?? 'Inicial' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="status-pill status-pill--req-{{ $log->to_status }}">
                                                        {{ $statusLabels[$log->to_status] ?? $log->to_status }}
                                                    </div>
                                                </td>
                                            <td>{{ $log->author?->name }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
