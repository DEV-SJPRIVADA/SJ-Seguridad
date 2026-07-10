<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Consolidado MADRE</h3>
                    <p class="panel-text">Vista consolidada por indicador a traves de todos los jefes de operaciones.</p>
                </div>
                <div class="panel__body">
                    <table class="supply-table js-datatable">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Indicador</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($indicators as $indicator)
                                <tr>
                                    <td>{{ $indicator->code }}</td>
                                    <td>{{ $indicator->name }}</td>
                                    <td>
                                        <a href="{{ route('indicadores.admin.mother.show', $indicator) }}" class="btn btn--secondary btn--sm">Ver MADRE</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
