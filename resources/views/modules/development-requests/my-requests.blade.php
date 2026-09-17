<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header panel__header--compact">
                    <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title">Mis solicitudes</h3>
                            <p class="panel-text panel-text--compact">Solicitudes de desarrollo creadas por usted.</p>
                        </div>
                        <a href="{{ route('development-requests.create', ['module' => $module]) }}" class="btn btn--primary btn--sm">Nueva</a>
                    </div>
                </div>
                <div class="panel__body">
                    @if (session('status'))
                        <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
                    @endif
                    <div class="data-table-wrap">
                        <table class="supply-table js-datatable" style="width:100%" data-dt-responsive="false" data-dt-compact="true">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Titulo</th>
                                    <th>Tipo</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($requests as $item)
                                    <tr>
                                        <td>{{ $item->code ?: '—' }}</td>
                                        <td>{{ $item->title }}</td>
                                        <td>{{ $item->tipoLabel() }}</td>
                                        <td>{{ $item->prioridadLabel() }}</td>
                                        <td>{{ $item->estadoLabel() }}</td>
                                        <td data-order="{{ $item->created_at?->timestamp }}">{{ $item->created_at?->format('Y-m-d') }}</td>
                                        <td>
                                            <a class="btn btn--secondary btn--sm" href="{{ route('development-requests.show', ['module' => $module, 'development_request' => $item, 'from' => 'mis_solicitudes']) }}">Ver</a>
                                            @if (in_array($item->status, ['borrador', 'devuelto'], true))
                                                <a class="btn btn--secondary btn--sm" href="{{ route('development-requests.edit', ['module' => $module, 'development_request' => $item]) }}">Editar</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7">Sin solicitudes.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
