<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Pesos del dashboard</h3>
                    <p class="panel-text">Distribucion ponderada para el score global (debe sumar 100%).</p>
                </div>
                <div class="panel__body">
                    <form method="POST" action="{{ route('indicadores.admin.weights.update') }}" class="form-stack">
                        @csrf
                        @method('PATCH')
                        <table class="supply-table">
                            <thead>
                                <tr>
                                    <th>Indicador</th>
                                    <th>Peso (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($indicators as $indicator)
                                    <tr>
                                        <td>{{ $indicator->code }} — {{ $indicator->name }}</td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100" name="weights[{{ $indicator->id }}]" class="supply-input"
                                                value="{{ old('weights.'.$indicator->id, $indicator->dashboardWeight?->weight ?? 0) }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div style="margin-top:1rem;">
                            <label class="form-label">Motivo del cambio</label>
                            <input type="text" name="reason" class="supply-input" required value="{{ old('reason') }}">
                        </div>
                        <button type="submit" class="btn btn--primary btn--sm" style="margin-top:1rem;">Guardar pesos</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
