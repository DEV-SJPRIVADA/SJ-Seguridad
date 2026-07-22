<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px; text-align: left; }
        th { background: #003366; color: #fff; }
        h2 { color: #003366; }
    </style>
</head>
<body>
    <h2>Consolidado</h2>
    <p><strong>Indicador:</strong> {{ $indicator->code }} - {{ $indicator->name }}</p>
    <p><strong>Periodo:</strong> {{ $year }}-{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}</p>

    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Numerador</th>
                <th>Denominador</th>
                <th>%</th>
                <th>Semaforo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($monthly['rows'] as $row)
                <tr>
                    <td>{{ $row['user']->name }}</td>
                    <td>{{ $row['capture']?->numerator }}</td>
                    <td>{{ $row['capture']?->denominator }}</td>
                    <td>{{ $row['result_percentage'] }}</td>
                    <td>{{ $row['semaforo'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
