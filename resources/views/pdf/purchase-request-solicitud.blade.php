<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud {{ $purchaseRequest->folio() }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1e293b;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }

        .page-header {
            border-bottom: 2px solid #003366;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }

        .page-header__top {
            width: 100%;
            border-collapse: collapse;
        }

        .page-header__top td {
            vertical-align: middle;
            padding: 0;
            border: none;
        }

        .brand-title {
            font-size: 13px;
            font-weight: bold;
            color: #003366;
            margin: 0;
        }

        .brand-subtitle {
            font-size: 8px;
            color: #64748b;
            margin: 0;
        }

        .form-meta {
            text-align: right;
            font-size: 7.5px;
            color: #475569;
            line-height: 1.25;
        }

        .form-meta strong { color: #003366; }

        .folio-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
        }

        .folio-row td {
            padding: 4px 6px;
            border: none;
            vertical-align: middle;
        }

        .folio-number {
            font-size: 11px;
            font-weight: bold;
            color: #003366;
        }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge--info { background: #dbeafe; color: #1d4ed8; }
        .badge--success { background: #dcfce7; color: #15803d; }
        .badge--danger { background: #fee2e2; color: #b91c1c; }
        .badge--warning { background: #fef3c7; color: #b45309; }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #003366;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin: 6px 0 3px;
            padding: 2px 0;
            border-bottom: 1px solid #cbd5e1;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .meta-table th,
        .meta-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 5px;
            vertical-align: top;
            font-size: 8.5px;
        }

        .meta-table th {
            width: 18%;
            background: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-align: left;
        }

        .meta-table td {
            width: 32%;
            color: #0f172a;
        }

        .meta-table .value-strong {
            font-weight: bold;
        }

        .text-inline {
            margin: 0 0 4px;
            padding: 3px 5px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .text-inline__label {
            font-weight: bold;
            color: #003366;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            vertical-align: top;
        }

        .items-table th {
            background: #003366;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
        }

        .items-table td { font-size: 8.5px; }

        .items-table .col-num { width: 20px; text-align: center; }
        .items-table .col-foto { width: 44px; text-align: center; }
        .items-table .col-qty { width: 36px; text-align: center; }
        .items-table .col-desc { font-weight: bold; color: #003366; }

        .item-photo {
            max-width: 38px;
            max-height: 30px;
            object-fit: contain;
        }

        .muted { color: #94a3b8; font-size: 7.5px; }

        .page-footer {
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            font-size: 7px;
            color: #64748b;
            text-align: center;
        }

        .notes-block { margin-top: 6px; }
    </style>
</head>
<body>
    @php
        $estadoLabel = match ($purchaseRequest->estado) {
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            default => 'Pendiente',
        };
        $estadoBadge = match ($purchaseRequest->estado) {
            'aprobado' => 'badge--success',
            'rechazado' => 'badge--danger',
            default => 'badge--info',
        };
    @endphp

    <div class="page-header">
        <table class="page-header__top">
            <tr>
                <td>
                    <p class="brand-title">{{ $reportTitle }}</p>
                    <p class="brand-subtitle">SJ Seguridad — Solicitud de compra</p>
                </td>
                <td class="form-meta">
                    <strong>{{ $formCode }}</strong> v{{ $formVersion }} · Generado {{ $generatedAt->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="folio-row">
        <tr>
            <td><span class="folio-number">Solicitud N.º {{ $purchaseRequest->folio() }}</span></td>
            <td style="text-align: right;">
                <span class="badge {{ $estadoBadge }}">{{ $estadoLabel }}</span>
                @if ($purchaseRequest->urgente)
                    <span class="badge badge--warning">Urgente</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <th>Fecha solicitud</th>
            <td class="value-strong">{{ optional($purchaseRequest->fecha_solicitud)->format('d/m/Y') ?? '—' }}</td>
            <th>Solicitante</th>
            <td class="value-strong">{{ $purchaseRequest->user?->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>Area</th>
            <td>{{ $purchaseRequest->areaLabel() ?? '—' }}</td>
            <th>Solicitud para</th>
            <td>{{ $purchaseRequest->solicitud_para }}</td>
        </tr>
        <tr>
            <th>Director aprobador</th>
            <td class="value-strong">{{ $purchaseRequest->aprobador?->name ?? '—' }}</td>
            <th>Fecha aprobacion</th>
            <td>{{ optional($purchaseRequest->fecha_aprobacion)->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @if ($purchaseRequest->estado_compras)
            <tr>
                <th>Estado compras</th>
                <td colspan="3">{{ $purchaseRequest->estadoComprasLabel() }}</td>
            </tr>
        @endif
        @if ($purchaseRequest->solicitud_para === 'Cliente')
            <tr>
                <th>Razon social</th>
                <td>{{ $purchaseRequest->razon_social ?? '—' }}</td>
                <th>Proy. nuevo / Asume</th>
                <td>{{ $purchaseRequest->proyecto_nuevo ? 'Si' : 'No' }} / {{ $purchaseRequest->asume_cliente ? 'Si' : 'No' }}</td>
            </tr>
        @endif
    </table>

    <div class="section-title">Productos solicitados</div>
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-foto">Foto</th>
                <th class="col-qty">Cant.</th>
                <th>Descripcion</th>
                <th>Referencia</th>
                <th>Utilizacion</th>
                <th>Ubicacion</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseRequest->items as $item)
                <tr>
                    <td class="col-num">{{ $item->orden ?? $loop->iteration }}</td>
                    <td class="col-foto">
                        @if (! empty($itemPhotos[$item->id]))
                            <img src="{{ $itemPhotos[$item->id] }}" alt="Foto" class="item-photo">
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td class="col-qty">{{ $item->cantidad }}</td>
                    <td class="col-desc">{{ $item->descripcion }}</td>
                    <td>{{ $item->referencia }}</td>
                    <td>{{ $item->utilizacion }}</td>
                    <td>{{ $item->ubicacion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($purchaseRequest->descripcion || $purchaseRequest->justificacion || $purchaseRequest->comentarios_director || $purchaseRequest->comentarios_compras)
        <div class="notes-block">
            @if ($purchaseRequest->descripcion)
                <p class="text-inline"><span class="text-inline__label">Descripcion:</span> {{ $purchaseRequest->descripcion }}</p>
            @endif
            @if ($purchaseRequest->justificacion)
                <p class="text-inline"><span class="text-inline__label">Justificacion:</span> {{ $purchaseRequest->justificacion }}</p>
            @endif
            @if ($purchaseRequest->comentarios_director)
                <p class="text-inline"><span class="text-inline__label">Comentarios director:</span> {{ $purchaseRequest->comentarios_director }}</p>
            @endif
            @if ($purchaseRequest->comentarios_compras)
                <p class="text-inline">
                    <span class="text-inline__label">Comentarios compras:</span> {{ $purchaseRequest->comentarios_compras }}
                    @if ($purchaseRequest->procesadoComprasPor)
                        <span class="muted">
                            — {{ $purchaseRequest->procesadoComprasPor->name }}
                            @if ($purchaseRequest->procesado_compras_at)
                                ({{ $purchaseRequest->procesado_compras_at->format('d/m/Y H:i') }})
                            @endif
                        </span>
                    @endif
                </p>
            @endif
        </div>
    @endif

    <div class="page-footer">
        Documento generado automaticamente — {{ $formCode }} v{{ $formVersion }} — SJ Seguridad
    </div>
</body>
</html>
