<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Aprobacion lider</h3>
                    <p class="panel-text">Solicitudes pendientes de validacion institucional.</p>
                </div>
                <div class="panel__body">
                    @if (session('status'))
                        <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
                    @endif
                    <div class="data-table-wrap">
                        <table class="supply-table js-datatable" style="width:100%" data-dt-compact="true">
                            <thead>
                                <tr>
                                    <th>Titulo</th>
                                    <th>Solicitante</th>
                                    <th>Area</th>
                                    <th>Prioridad</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $item)
                                    <tr>
                                        <td>{{ $item->title }}</td>
                                        <td>{{ $item->requester_name }}</td>
                                        <td>{{ $item->areaLabel() }}</td>
                                        <td>{{ $item->prioridadLabel() }}</td>
                                        <td>{{ $item->created_at?->format('Y-m-d') }}</td>
                                        <td>
                                            <a class="btn btn--secondary btn--sm" href="{{ route('development-requests.show', ['module' => $module, 'development_request' => $item, 'from' => 'leader_approval']) }}">Revisar</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6">No hay pendientes.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
