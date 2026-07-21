<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container indicadores-board">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Consolidado</h3>
                    <p class="panel-text">Vista consolidada por indicador a traves de los usuarios de captura.</p>
                </div>
                <div class="panel__body">
                    <div class="indicadores-table-wrap">
                        <table class="supply-table js-datatable indicadores-table indicadores-table--mother" data-no-excel>
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
                                        <td><span class="indicadores-code">{{ $indicator->code }}</span></td>
                                        <td class="indicadores-cell-wrap">{{ $indicator->name }}</td>
                                        <td>
                                            <a href="{{ route('indicadores.admin.mother.show', $indicator) }}" class="btn btn--secondary btn--sm">Ver consolidado</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
