<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Suministro {{ $supplyRequest->folio() }}</title>
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
        $estadoBadge = match ($supplyRequest->status) {
            'aprobada_calidad', 'completada' => 'badge--success',
            'rechazada_calidad' => 'badge--danger',
            'en_compras' => 'badge--info',
            default => 'badge--info',
        };
    @endphp

    <div class="page-header">
        <table class="page-header__top">
            <tr>
                <td>
                    <p class="brand-title">{{ $reportTitle }}</p>
                    <p class="brand-subtitle">SJ Seguridad — Solicitud de suministro</p>
                </td>
                <td class="form-meta">
                    <strong>{{ $formCode }}</strong> v{{ $formVersion }} · Generado {{ $generatedAt->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="folio-row">
        <tr>
            <td><span class="folio-number">Solicitud N.º {{ $supplyRequest->folio() }}</span></td>
            <td style="text-align: right;">
                <span class="badge {{ $estadoBadge }}">{{ $supplyRequest->statusLabel() }}</span>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <th>Fecha solicitud</th>
            <td class="value-strong">{{ $supplyRequest->created_at->format('d/m/Y') }}</td>
            <th>Solicitante</th>
            <td class="value-strong">{{ $supplyRequest->user?->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>Area</th>
            <td>{{ config("access.areas.{$supplyRequest->area_key}", $supplyRequest->area_key) }}</td>
            <th>Sede / utilizacion</th>
            <td>{{ $supplyRequest->site_utilization ?? '—' }}</td>
        </tr>
        <tr>
            <th>Ubicacion</th>
            <td>{{ $supplyRequest->site_city ?? '—' }}</td>
            <th>Revisor calidad</th>
            <td class="value-strong">{{ $supplyRequest->qualityReviewer?->name ?? '—' }}</td>
        </tr>
        @if ($supplyRequest->estadoComprasLabel())
            <tr>
                <th>Estado compras</th>
                <td colspan="3">{{ $supplyRequest->estadoComprasLabel() }}</td>
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
            @forelse ($lineItems as $item)
                <tr>
                    <td class="col-num">{{ $loop->iteration }}</td>
                    <td class="col-foto"><span class="muted">—</span></td>
                    <td class="col-qty">{{ $item['quantity'] }}</td>
                    <td class="col-desc">{{ $item['description'] }}</td>
                    <td>{{ $item['reference'] }}</td>
                    <td>{{ $item['utilization'] }}</td>
                    <td>{{ $item['location'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted" style="text-align: center;">Sin productos autorizados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($supplyRequest->observations || $supplyRequest->quality_observations || $supplyRequest->purchasingManager)
        <div class="notes-block">
            @if ($supplyRequest->observations)
                <p class="text-inline"><span class="text-inline__label">Notas del solicitante:</span> {{ $supplyRequest->observations }}</p>
            @endif
            @if ($supplyRequest->quality_observations)
                <p class="text-inline"><span class="text-inline__label">Observaciones calidad:</span> {{ $supplyRequest->quality_observations }}</p>
            @endif
            @if ($supplyRequest->purchasingManager)
                <p class="text-inline">
                    <span class="text-inline__label">Procesamiento compras:</span>
                    {{ $supplyRequest->purchasingManager->name }}
                    @if ($supplyRequest->total_cost !== null)
                        <span class="muted"> — Costo total: ${{ number_format((float) $supplyRequest->total_cost, 2) }}</span>
                    @endif
                    @if ($supplyRequest->status === 'completada')
                        <span class="muted"> ({{ $supplyRequest->updated_at->format('d/m/Y H:i') }})</span>
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
