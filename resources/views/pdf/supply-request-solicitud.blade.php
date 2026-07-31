<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Suministro {{ $supplyRequest->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; }
        th { background: #003366; color: #fff; }
        h1 { font-size: 14px; }
    </style>
</head>
<body>
    <h1>{{ $reportTitle }}</h1>
    <p><strong>Formulario:</strong> {{ $formCode }} | <strong>Solicitud #:</strong> {{ $supplyRequest->id }}</p>
    <p><strong>Solicitante:</strong> {{ $supplyRequest->user?->name }}</p>
    <table>
        <thead>
            <tr>
                <th>Cantidad</th>
                <th>Descripcion</th>
                <th>Referencia</th>
                <th>Utilizacion</th>
                <th>Ubicacion</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['quantity'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ $row['reference'] }}</td>
                    <td>{{ $row['utilization'] }}</td>
                    <td>{{ $row['location'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
