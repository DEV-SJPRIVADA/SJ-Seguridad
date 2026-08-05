<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dashboard Operaciones {{ $year }}-{{ $month }}</title>
    @include('areas.operaciones.exports.partials.pdf-base-styles')
</head>
<body>
    <h1>Dashboard General de Operaciones — {{ config('indicators.months')[$month] ?? $month }} {{ $year }}</h1>

    <table class="dashboard-stats">
        <tr>
            <td>
                <div class="caption">Score global ponderado</div>
                <div class="value">{{ number_format($dashboard['global_score'], 2) }}%</div>
            </td>
            <td>
                <div class="caption">Estado general</div>
                <div class="value">{{ $dashboard['global_state'] }}</div>
            </td>
            <td>
                <div class="caption">Regla</div>
                <div style="font-size:10px;">>=90 ESTABLE | 75-89 ATENCION | &lt;75 CRITICO</div>
            </td>
        </tr>
    </table>

    <h4 class="section-title">KPIs del mes</h4>
    <table class="dashboard-table">
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Indicador</th>
                <th>Mes anterior ({{ $dashboard['previous_period']['label'] ?? '' }})</th>
                <th>Resultado</th>
                <th>Meta</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dashboard['kpis'] as $kpi)
                <tr>
                    <td>{{ $kpi['indicator']->code }}</td>
                    <td>{{ $kpi['indicator']->name }}</td>
                    <td>{{ $kpi['previous_result'] !== null ? number_format((float) $kpi['previous_result'], 2).'%' : '-' }}</td>
                    <td>{{ $kpi['result'] !== null ? number_format((float) $kpi['result'], 2).'%' : '-' }}</td>
                    <td>{{ $kpi['meta'] }}</td>
                    <td>{{ $kpi['semaforo'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="split-table">
        <tr>
            <td>
                <h4 class="section-title">Ranking de usuarios</h4>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Usuario</th>
                            <th>Indicadores gestionados</th>
                            <th>% gestionado</th>
                            <th>Mejoras ingresadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dashboard['zone_ranking'] as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row['user']->name }}</td>
                                <td>{{ $row['indicators_managed'] }}</td>
                                <td>{{ $row['management_percentage'] }}%</td>
                                <td>{{ $row['improvements_count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No hay usuarios con capturas registradas en este periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <h4 class="section-title">Indicadores criticos</h4>
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Indicador</th>
                            <th>Valor critico</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dashboard['critical_indicators'] as $row)
                            <tr>
                                <td>{{ $row['user']->name }}</td>
                                <td>{{ $row['indicator']->code }} — {{ $row['indicator']->name }}</td>
                                <td>{{ number_format((float) $row['critical_value'], 2) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No hay indicadores en estado critico para este periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
