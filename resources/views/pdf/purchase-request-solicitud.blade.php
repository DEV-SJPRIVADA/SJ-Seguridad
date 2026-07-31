<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud {{ $purchaseRequest->folio() }}</title>
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
    <p><strong>Formulario:</strong> {{ $formCode }} | <strong>Solicitud N.º:</strong> {{ $purchaseRequest->folio() }}</p>
    <p><strong>Solicitante:</strong> {{ $purchaseRequest->user?->name }} | <strong>Area:</strong> {{ $purchaseRequest->areaLabel() }}</p>
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
            @foreach ($purchaseRequest->items as $item)
                <tr>
                    <td>{{ $item->cantidad }}</td>
                    <td>{{ $item->descripcion }}</td>
                    <td>{{ $item->referencia }}</td>
                    <td>{{ $item->utilizacion }}</td>
                    <td>{{ $item->ubicacion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
