<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        h2 { margin: 0 0 6px 0; color: #003366; }
    </style>
</head>
<body>
    <h2>Reporte de captura</h2>
    <p><strong>Indicador:</strong> {{ $indicator->code }} - {{ $indicator->name }}</p>
    <p><strong>Usuario:</strong> {{ $user->name }}</p>
    <p><strong>Periodo:</strong> {{ $year }}-{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}</p>

    <table>
        <thead><tr><th>Campo</th><th>Valor</th></tr></thead>
        <tbody>
            @foreach ($display as $key => $value)
                <tr>
                    <td>{{ $key }}</td>
                    <td>{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}</td>
                </tr>
            @endforeach
            <tr><td>Resultado %</td><td>{{ $capture?->result_percentage }}</td></tr>
            <tr><td>Semaforo</td><td>{{ $capture ? ($capture->complies ? 'VERDE' : 'ROJO') : '-' }}</td></tr>
            <tr><td>Analisis</td><td>{{ $capture?->analysis_text }}</td></tr>
        </tbody>
    </table>

    @if ($capture?->improvement)
        <h3>Mejora</h3>
        <p><strong>Analisis:</strong> {{ $capture->improvement->analysis }}</p>
        <p><strong>Accion tomada:</strong> {{ $capture->improvement->action_taken }}</p>
        <p><strong>Accion definida:</strong> {{ $capture->improvement->action_defined }}</p>
    @endif
</body>
</html>
