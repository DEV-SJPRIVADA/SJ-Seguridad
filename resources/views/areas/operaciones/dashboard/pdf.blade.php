<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Dashboard Operaciones {{ $year }}-{{ $month }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; color: #003366; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #003366; color: #fff; }
    </style>
</head>
<body>
    <h1>Dashboard General de Operaciones — {{ config('indicators.months')[$month] ?? $month }} {{ $year }}</h1>
    <p><strong>Score global:</strong> {{ number_format($dashboard['global_score'], 2) }}% — {{ $dashboard['global_state'] }}</p>

    @if ($summary?->summary_text)
        <p><strong>Resumen ejecutivo:</strong> {{ $summary->summary_text }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Codigo</th>
                <th>Indicador</th>
                <th>Mes anterior ({{ config('indicators.months')[$dashboard['previous_period']['month'] ?? 0] ?? '' }})</th>
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
</body>
</html>
