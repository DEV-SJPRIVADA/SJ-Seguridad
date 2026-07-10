<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Captura de indicadores</h3>
                    <p class="panel-text">Selecciona un indicador para registrar o revisar la captura mensual.</p>
                </div>
                <div class="panel__body">
                    <table class="supply-table js-datatable">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Indicador</th>
                                <th>Meta</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($indicators as $indicator)
                                <tr>
                                    <td>{{ $indicator->code }}</td>
                                    <td>{{ $indicator->name }}</td>
                                    <td>{{ $indicator->target_operator }} {{ number_format((float) $indicator->target_value, 2) }}%</td>
                                    <td>
                                        <a href="{{ route('indicadores.show', $indicator) }}" class="btn btn--secondary btn--sm">Capturar</a>
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
