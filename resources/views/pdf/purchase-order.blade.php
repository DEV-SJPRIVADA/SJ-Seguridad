{{-- FO-AD-07 oficial: cuadrícula de una hoja carta. Sí/No solo si el snapshot es SI/NO. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Orden de compra {{ $order->numeroOc }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 10pt 12pt 8pt 12pt;
        }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #111;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }

        table { width: 100%; border-collapse: collapse; }

        td, th {
            border: 0.6pt solid #1a1a1a;
            padding: 1px 3px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 18%;
            text-align: center;
            padding: 2px;
        }

        .logo-cell img { height: 42px; width: auto; }

        .title-cell {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.02em;
        }

        .anulada-mark {
            display: inline-block;
            margin-top: 2px;
            padding: 1px 8px;
            border: 1.2pt solid #b91c1c;
            color: #b91c1c;
            font-size: 12px;
            letter-spacing: 0.12em;
        }

        .meta-cell {
            width: 22%;
            text-align: center;
            font-size: 8px;
            font-weight: bold;
            padding: 1px 3px;
        }

        .label {
            width: 22%;
            font-weight: bold;
            background: #e8eef6;
            color: #003366;
        }

        .section {
            text-align: center;
            font-weight: bold;
            background: #003366;
            color: #fff;
            font-size: 8px;
            letter-spacing: 0.03em;
        }

        .items th {
            background: #003366;
            color: #fff;
            font-size: {{ $itemFontSize }};
            font-weight: bold;
            text-align: center;
            padding: {{ $itemCellPadding }};
        }

        .items td {
            font-size: {{ $itemFontSize }};
            padding: {{ $itemCellPadding }};
        }

        .items .num { text-align: right; white-space: nowrap; }
        .items .qty { text-align: center; width: 11%; }
        .items .total-row td { font-weight: bold; background: #f4f7fb; }

        .obs-head {
            text-align: center;
            font-weight: bold;
            background: #003366;
            color: #fff;
            letter-spacing: 0.04em;
        }

        .obs-body {
            height: {{ $obsHeight }};
            vertical-align: top;
            white-space: pre-wrap;
        }

        .sign-head { font-weight: bold; text-align: center; background: #003366; color: #fff; font-size: 7px; }
        .sign-space { height: {{ $signatureHeight }}; vertical-align: bottom; text-align: center; font-size: 7.5px; background: #fff; }
        .sign-role { text-align: center; font-size: 6.5px; font-weight: bold; background: #e8eef6; color: #003366; }

        .flag-asume,
        .flag-proyecto,
        .flag-reinversion,
        .flag-comodato,
        .flag-activo {
            text-align: left;
            font-weight: bold;
            color: #003366;
            background: #fff;
        }

        .legal {
            margin: 4px 0 0;
            padding: 4px 5px;
            border: 0.6pt solid #1a1a1a;
            font-size: 6px;
            line-height: 1.25;
            text-align: left;
        }

        .legal p {
            margin: 0 0 5px;
        }

        .legal p:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="logo-cell" rowspan="4">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="SJ Seguridad">
                @endif
            </td>
            <td class="title-cell" rowspan="4">
                {{ $reportTitle }}
                {{-- Marca visible si la orden fue anulada con aprobación del director. --}}
                @if ($order->isAnulada())
                    <br><span class="anulada-mark">ANULADA</span>
                @endif
            </td>
            <td class="meta-cell">{{ $formCode }}</td>
        </tr>
        <tr>
            <td class="meta-cell">{{ $formPeriod }}</td>
        </tr>
        <tr>
            <td class="meta-cell">Versión {{ $formVersion }}</td>
        </tr>
        <tr>
            <td class="meta-cell">{{ $pageLabel }}</td>
        </tr>
        <tr>
            <td class="label">Fecha(dd/mm/aaaa)</td>
            <td>{{ $order->fechaImpresa() }}</td>
            <td class="label">OC No. {{ $order->numeroOc }}</td>
        </tr>
        <tr>
            <td class="label">Ciudad</td>
            <td colspan="2">{{ $order->ciudad }}</td>
        </tr>
        <tr>
            <td class="section" colspan="3">Información del Asociado de Negocio</td>
        </tr>
        <tr>
            <td class="label">Razón social</td>
            <td colspan="2">{{ $order->provider_nombre }}</td>
        </tr>
        <tr>
            <td class="label">NIT</td>
            <td colspan="2">{{ $order->provider_identificacion }}</td>
        </tr>
        <tr>
            <td class="label">Contacto</td>
            <td colspan="2">{{ $order->provider_contacto }}</td>
        </tr>
        <tr>
            <td class="label">Teléfono</td>
            <td colspan="2">{{ $order->provider_telefono }}</td>
        </tr>
        <tr>
            <td class="label">Correo electrónico</td>
            <td colspan="2">{{ $order->provider_correo }}</td>
        </tr>
        <tr>
            <td class="label">Dirección</td>
            <td colspan="2">{{ $order->provider_direccion }}</td>
        </tr>
        <tr>
            <td class="label">Ciudad</td>
            <td colspan="2">{{ $order->provider_ciudad }}</td>
        </tr>
        <tr>
            <td class="label">Forma de pago</td>
            <td colspan="2">{{ $order->formaPagoImpresa() }}</td>
        </tr>
        {{-- Sí/No junto a la etiqueta, igual que Razón social o Forma de pago. --}}
        <tr>
            <td class="label">Asume el cliente</td>
            <td colspan="2" class="flag-asume">{{ $order->banderaImpresa('asume_cliente') }}</td>
        </tr>
        <tr>
            <td class="label">Proyecto nuevo</td>
            <td colspan="2" class="flag-proyecto">{{ $order->banderaImpresa('proyecto_nuevo') }}</td>
        </tr>
        <tr>
            <td class="label">Reinversión</td>
            <td colspan="2" class="flag-reinversion">{{ $order->banderaImpresa('reinversion') }}</td>
        </tr>
        <tr>
            <td class="label">Comodato</td>
            <td colspan="2" class="flag-comodato">{{ $order->banderaImpresa('comodato') }}</td>
        </tr>
        <tr>
            <td class="label">Activo</td>
            <td colspan="2" class="flag-activo">{{ $order->banderaImpresa('activo') }}</td>
        </tr>
    </table>

    <table class="items" style="margin-top: 3px;">
        <tr>
            <th colspan="6">Sírvase suministrar los servicios y/o artículos descritos a continuación:</th>
        </tr>
        <tr>
            <th class="qty">Cantidad</th>
            <th>Artículo</th>
            <th>Descripción</th>
            <th class="num">Valor unidad</th>
            <th class="num">IVA</th>
            <th class="num">Valor total</th>
        </tr>
        @foreach ($order->items as $item)
            <tr>
                <td class="qty">{{ rtrim(rtrim(number_format((float) $item->cantidad, 2, '.', ''), '0'), '.') }}</td>
                <td>{{ $item->articulo }}</td>
                <td>{{ $item->descripcion }}</td>
                <td class="num">${{ number_format((float) $item->valor_unidad, 2) }}</td>
                {{-- Valor del IVA y, si se eligió del catálogo, la tasa: $19.00 (19%). --}}
                <td class="num">${{ $item->ivaImpreso() }}</td>
                <td class="num">${{ number_format((float) $item->valor_total, 2) }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="5" style="text-align: right;">TOTAL</td>
            <td class="num">${{ number_format((float) $order->total, 2) }}</td>
        </tr>
    </table>

    <table style="margin-top: 3px;">
        <tr>
            <td class="obs-head">OBSERVACIONES</td>
        </tr>
        <tr>
            <td class="obs-body">{{ $order->observaciones }}</td>
        </tr>
    </table>

    <table style="margin-top: 3px;">
        <tr>
            <td class="sign-head">Elaborado por:</td>
            <td class="sign-head">Revisado y aprobado por:</td>
            <td class="sign-head" colspan="2">Aprobado 2 por:</td>
        </tr>
        <tr>
            <td class="sign-space">{{ $order->elaboradoNombre() }}</td>
            <td class="sign-space">{{ $order->revisorNombre() }}</td>
            <td class="sign-space"></td>
            <td class="sign-space"></td>
        </tr>
        <tr>
            <td class="sign-role">Analista de Compras</td>
            <td class="sign-role">Director(a) área solicitante</td>
            <td class="sign-role">Director Admon. y Financiero</td>
            <td class="sign-role">Gerente (cuando aplique)</td>
        </tr>
    </table>

    <div class="legal">
        @foreach (preg_split('/\R{2,}/', (string) $condiciones) as $parrafo)
            @if (trim($parrafo) !== '')
                <p>{{ trim($parrafo) }}</p>
            @endif
        @endforeach
    </div>
</body>
</html>
