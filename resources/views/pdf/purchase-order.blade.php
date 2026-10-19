<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de compra {{ $order->numeroOc }}</title>
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

        .page-header__top { width: 100%; border-collapse: collapse; }
        .page-header__top td { vertical-align: middle; padding: 0; border: none; }

        .brand-title { font-size: 13px; font-weight: bold; color: #003366; margin: 0; }
        .brand-subtitle { font-size: 8px; color: #64748b; margin: 0; }

        .form-meta { text-align: right; font-size: 7.5px; color: #475569; line-height: 1.25; }
        .form-meta strong { color: #003366; }

        .folio-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
        }

        .folio-row td { padding: 4px 6px; border: none; vertical-align: middle; }
        .folio-number { font-size: 11px; font-weight: bold; color: #003366; }

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

        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }

        .meta-table th,
        .meta-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 5px;
            vertical-align: top;
            font-size: 8.5px;
        }

        .meta-table th { width: 18%; background: #f1f5f9; color: #475569; font-weight: bold; text-align: left; }
        .meta-table td { width: 32%; color: #0f172a; }
        .meta-table .value-strong { font-weight: bold; }

        .text-inline {
            margin: 0 0 4px;
            padding: 3px 5px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 8.5px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .text-inline__label { font-weight: bold; color: #003366; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 2px; }

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
        .items-table .num { text-align: right; white-space: nowrap; }
        .items-table .col-qty { width: 48px; text-align: center; }
        .items-table .col-desc { font-weight: bold; color: #003366; }

        .totals-table { width: 45%; margin-left: 55%; border-collapse: collapse; margin-top: 4px; }
        .totals-table td { border: none; padding: 2px 4px; font-size: 8.5px; }
        .totals-table td.label { text-align: right; color: #475569; }
        .totals-table td.value { text-align: right; font-weight: bold; white-space: nowrap; }
        .totals-table tr.total td { border-top: 1px solid #003366; font-size: 10px; color: #003366; }

        .signatures { width: 100%; border-collapse: collapse; margin-top: 26px; }
        .signatures td { width: 33.33%; text-align: center; padding: 0 8px; vertical-align: bottom; }
        .signatures .line { border-top: 1px solid #475569; padding-top: 3px; font-size: 8px; color: #475569; }

        .page-footer {
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            font-size: 7px;
            color: #64748b;
            text-align: center;
        }

        .muted { color: #94a3b8; font-size: 7.5px; }
    </style>
</head>
<body>
    @php
        $subtotal = $order->items->sum(fn ($item) => ((float) $item->cantidad) * ((float) $item->valor_unidad));
        $ivaTotal = $order->items->sum(fn ($item) => (float) $item->iva);
    @endphp

    <div class="page-header">
        <table class="page-header__top">
            <tr>
                <td>
                    <p class="brand-title">{{ $reportTitle }}</p>
                    <p class="brand-subtitle">{{ $company }} — Orden de compra</p>
                </td>
                <td class="form-meta">
                    <strong>{{ $formCode }}</strong><br>
                    {{ $formPeriod }} · Versión {{ $formVersion }}<br>
                    {{ $pageLabel }}<br>
                    Generado {{ $generatedAt->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="folio-row">
        <tr>
            <td><span class="folio-number">Orden de compra N.º {{ $order->numeroOc }}</span></td>
            <td style="text-align: right;">
                <span class="badge {{ $order->estado === 'emitida' ? 'badge--success' : 'badge--info' }}">{{ $order->estadoLabel() }}</span>
            </td>
        </tr>
    </table>

    <div class="section-title">Proveedor</div>
    <table class="meta-table">
        <tr>
            <th>Identificación</th>
            <td class="value-strong">{{ $order->provider_identificacion }}</td>
            <th>Nombre</th>
            <td class="value-strong">{{ $order->provider_nombre }}</td>
        </tr>
        <tr>
            <th>Contacto</th>
            <td>{{ $order->provider_contacto ?? '—' }}</td>
            <th>Teléfono</th>
            <td>{{ $order->provider_telefono ?? '—' }}</td>
        </tr>
        <tr>
            <th>Correo</th>
            <td>{{ $order->provider_correo ?? '—' }}</td>
            <th>Ciudad</th>
            <td>{{ $order->provider_ciudad ?? '—' }}</td>
        </tr>
        <tr>
            <th>Dirección</th>
            <td>{{ $order->provider_direccion ?? '—' }}</td>
            <th>Forma de pago</th>
            <td>{{ $order->provider_forma_pago ?? '—' }}</td>
        </tr>
        <tr>
            <th>Días de pago</th>
            <td>{{ $order->provider_dias_pago !== null ? $order->provider_dias_pago : '—' }}</td>
            <th>Ciudad de entrega</th>
            <td>{{ $order->ciudad ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Datos de la orden</div>
    <table class="meta-table">
        <tr>
            <th>Fecha</th>
            <td class="value-strong">{{ $order->fecha }}</td>
            <th>Origen</th>
            <td>{{ $order->sourceLabel() }} N.º {{ $order->sourceFolio() }}</td>
        </tr>
        @if ($order->asume_cliente !== null)
            <tr>
                <th>Asume cliente</th>
                <td colspan="3">{{ $order->asume_cliente }}</td>
            </tr>
        @endif
    </table>

    <div class="section-title">Detalle</div>
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-qty">#</th>
                <th class="col-qty">Cantidad</th>
                <th>Artículo</th>
                <th>Descripción</th>
                <th class="num">Valor unidad</th>
                <th class="num">IVA</th>
                <th class="num">Valor total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="col-qty">{{ $item->orden }}</td>
                    <td class="col-qty">{{ $item->cantidad }}</td>
                    <td>{{ $item->articulo ?? '—' }}</td>
                    <td class="col-desc">{{ $item->descripcion ?? '—' }}</td>
                    <td class="num">{{ number_format((float) $item->valor_unidad, 2) }}</td>
                    <td class="num">{{ number_format((float) $item->iva, 2) }}</td>
                    <td class="num">{{ number_format((float) $item->valor_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format((float) $subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">IVA</td>
            <td class="value">{{ number_format((float) $ivaTotal, 2) }}</td>
        </tr>
        <tr class="total">
            <td class="label">TOTAL</td>
            <td class="value">{{ number_format((float) $order->total, 2) }}</td>
        </tr>
    </table>

    @if ($order->observaciones)
        <div class="section-title">Observaciones</div>
        <p class="text-inline">{{ $order->observaciones }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td><div class="line">Elaboró</div></td>
            <td><div class="line">Aprobó</div></td>
            <td><div class="line">Recibió</div></td>
        </tr>
    </table>

    <div class="page-footer">
        Documento generado automáticamente — {{ $formCode }} · {{ $formPeriod }} Versión {{ $formVersion }} — {{ $company }}
    </div>
</body>
</html>
