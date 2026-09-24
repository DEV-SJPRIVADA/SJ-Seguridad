<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ ($blank ?? false) ? 'Requisición de Personal - Plantilla FO-GH-22' : 'Requisición de Personal - '.$requisition->code }}</title>
    <style>
        @page {
            size: letter;
            margin: 0.5cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 7.2pt;
            line-height: 1.1;
            color: #000;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .container {
            width: 100%;
            max-width: 21.59cm; /* Tamaño Carta */
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: -1px;
        }
        th, td {
            border: 1px solid #000;
            padding: 1.5px 4px;
            text-align: left;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .text-center { text-align: center; }
        .bg-gray { background-color: #e0e0e0 !important; font-weight: bold; }
        .header-table td { height: 20px; }
        .row-h15 td { height: 12pt; }
        .value-row td {
            height: 14pt;
            min-height: 14pt;
            vertical-align: middle;
        }
        .section-title {
            background-color: #e0e0e0 !important;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.2pt;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .checkbox-cell {
            width: 18px;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
            height: 14pt;
            min-height: 14pt;
            vertical-align: middle;
        }
        .label-cell {
            background-color: #f2f2f2 !important;
            font-weight: bold;
            width: 28%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .signature-box {
            height: 45px;
            vertical-align: bottom;
            text-align: center;
            padding-bottom: 2px;
            font-size: 6.5pt;
        }
        .important-section {
            font-size: 6.2pt;
            padding: 4px;
            border: 1px solid #000;
            line-height: 1.1;
        }
        .no-print {
            background: #444;
            color: white;
            padding: 6px;
            text-align: center;
            margin-bottom: 5px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
@php
    $isBlank = (bool) ($blank ?? false);
    $mark = static function (bool $condition) use ($isBlank): string {
        if ($isBlank) {
            return "\u{00A0}";
        }

        return $condition ? 'X' : "\u{00A0}";
    };
    $money = static function ($value) use ($isBlank): string {
        if ($isBlank) {
            return '$ ';
        }

        return '$ '.number_format((float) $value, 0);
    };
    $cell = static function (?string $value = null) use ($isBlank): string {
        if ($isBlank) {
            return "\u{00A0}";
        }

        $text = (string) ($value ?? '');

        return $text === '' ? "\u{00A0}" : $text;
    };
@endphp

    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; font-weight: bold; background: #2563eb; color: white; border: none; border-radius: 4px;">
            {{ $isBlank ? 'IMPRIMIR PLANTILLA VACÍA (TAMAÑO CARTA)' : 'IMPRIMIR FORMATO (TAMAÑO CARTA)' }}
        </button>
    </div>

    <div class="container">
        {{-- HEADER --}}
        <table class="header-table">
            <tr>
                <td style="width: 20%;" class="text-center">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="height: 50px; width: auto; display: block; margin: 0 auto;">
                </td>
                <td style="width: 60%;" class="text-center">
                    <h1 style="margin: 0; font-size: 11pt;">REQUISICIÓN DE PERSONAL</h1>
                </td>
                <td style="width: 20%; font-size: 7.5pt; padding: 0;">
                    <table style="border: none; margin: 0; width: 100%;">
                        <tr><td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px;">FO-GH-22</td></tr>
                        <tr><td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px;">Febrero de 2026</td></tr>
                        <tr><td style="border: none; border-bottom: 1px solid #000; padding: 1px 3px;">Versión 06</td></tr>
                        <tr><td style="border: none; padding: 1px 3px;">Página 1 de 1</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- GENERAL INFO --}}
        <table>
            <tr class="bg-gray">
                <td style="width: 25%;" class="text-center">NRO.REQUISICION</td>
                <td style="width: 25%;" class="text-center">FECHA DE SOLICITUD</td>
                <td style="width: 50%;" class="text-center">FECHA Y HORA DE RECEPCIÓN EN GESTIÓN HUMANA</td>
            </tr>
            <tr class="value-row">
                <td class="text-center" style="font-weight: bold; font-size: 11pt;">{{ $cell($isBlank ? null : $requisition->code) }}</td>
                <td class="text-center">
                    @if ($isBlank)
                        {{ $cell() }}
                    @else
                        <x-date-table :value="$requisition->request_date" empty="" />
                    @endif
                </td>
                <td class="text-center">
                    @if ($isBlank)
                        {{ $cell() }}
                    @else
                        <x-date-table :value="$requisition->humanResourcesReceivedAt()" datetime empty="" />
                    @endif
                </td>
            </tr>
        </table>

        <table>
            <tr class="bg-gray">
                <td style="width: 50%;" class="text-center">LIDER Y/O JEFE DE AREA QUIEN SOLICITA</td>
                <td style="width: 50%;" class="text-center">PROCESO / ÁREA QUE SOLICITA</td>
            </tr>
            <tr class="value-row">
                <td class="text-center">{{ $cell($isBlank ? null : $requisition->leader_name) }}</td>
                <td class="text-center">{{ $cell($isBlank ? null : $moduleLabel) }}</td>
            </tr>
        </table>

        {{-- CARGO SOLICITADO --}}
        <table class="row-h15">
            <tr>
                <td colspan="3" class="section-title" style="text-decoration: underline;">CARGO SOLICITADO</td>
            </tr>
            <tr class="bg-gray">
                <td style="width: 70%;">NOMBRE DEL CARGO</td>
                <td style="width: 15%;" class="text-center">F</td>
                <td style="width: 15%;" class="text-center">M</td>
            </tr>
            <tr class="value-row">
                <td>{{ $cell($isBlank ? null : ($requisition->position?->name ?: '')) }}</td>
                <td class="checkbox-cell">{{ $mark(($requisition->sex ?? null) == 'femenino' || ($requisition->sex ?? null) == 'indiferente') }}</td>
                <td class="checkbox-cell">{{ $mark(($requisition->sex ?? null) == 'masculino' || ($requisition->sex ?? null) == 'indiferente') }}</td>
            </tr>
            <tr>
                <td colspan="3" style="padding: 0; border: none;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr class="value-row">
                            <td class="bg-gray text-center" style="width: 22%; white-space: nowrap;">A QUIEN REMPLAZA:</td>
                            <td style="width: 78%;">{{ $cell($isBlank ? null : ($requisition->replacement_name ?? '')) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- CONTRATO Y AREA --}}
        <table>
            <tr class="bg-gray">
                <td colspan="6" style="width: 60%;" class="text-center">TIPO DE CONTRATO (Marque con una x)</td>
                <td colspan="4" style="width: 40%;" class="text-center">AREA (Marque con una x)</td>
            </tr>
            <tr class="row-h12">
                <td style="width: 15%;">Obra Labor</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->contractType?->name ?? '', 'Obra') !== false) }}</td>
                <td style="width: 15%;">Fijo</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->contractType?->name ?? '', 'Fijo') !== false) }}</td>
                <td style="width: 15%;">Indefinido</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->contractType?->name ?? '', 'Indefinido') !== false) }}</td>

                <td style="width: 15%;">Administrativo</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(($requisition->operating_area_key ?? null) == 'administrativa') }}</td>
                <td style="width: 15%;">Operativo</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(($requisition->operating_area_key ?? null) == 'operaciones') }}</td>
            </tr>
        </table>

        {{-- MOTIVO --}}
        <table>
            <tr class="bg-gray">
                <td colspan="10" class="text-center">MOTIVO DE LA SOLICITUD (Marque con una X):</td>
            </tr>
            <tr>
                <td style="width: 15%;">Renuncia</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->requestReason?->name ?? '', 'Renuncia') !== false) }}</td>
                <td style="width: 15%;">Traslado</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->requestReason?->name ?? '', 'Traslado') !== false) }}</td>
                <td style="width: 15%;">Cliente Nuevo</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->requestReason?->name ?? '', 'Servicio nuevo') !== false || stripos($requisition->requestReason?->name ?? '', 'Cliente nuevo') !== false) }}</td>
                <td style="width: 15%;">Promoción</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->requestReason?->name ?? '', 'Promoción') !== false) }}</td>
                <td style="width: 15%;">Cargo Nuevo</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->requestReason?->name ?? '', 'Cargo nuevo') !== false) }}</td>
            </tr>
            <tr class="value-row">
                <td class="bg-gray">DURACIÓN DEL CONTRATO:</td>
                <td colspan="9">{{ $cell($isBlank ? null : ($requisition->contract_duration ?? '')) }}</td>
            </tr>
        </table>

        {{-- SALARIO --}}
        <table class="row-h15">
            <tr>
                <td colspan="2" class="section-title" style="text-decoration: underline;">DETALLE DE SALARIO</td>
            </tr>
            <tr>
                <td class="label-cell">Valor Salario Base:</td>
                <td>{{ $money($requisition->base_salary ?? null) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Auxilio de Transporte Legal:</td>
                <td>{{ $money($requisition->transport_allowance ?? null) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Auxilio de Movilización NO prestacional:</td>
                <td>{{ $money($requisition->mobility_allowance ?? null) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Bonificación Prestacional:</td>
                <td>{{ $money($requisition->statutory_bonus ?? null) }}</td>
            </tr>
            <tr>
                <td class="label-cell">Bonificación NO Prestacional:</td>
                <td>{{ $money($requisition->non_statutory_bonus ?? null) }}</td>
            </tr>
            <tr class="value-row">
                <td class="label-cell">Otros valores, cuales:</td>
                <td>{{ $cell($isBlank ? null : ($requisition->other_allowances ?: '')) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 0;">
                    <table style="width: 100%; border-collapse: collapse; border: none; margin: -1px;">
                        <tr>
                            <td style="border: none; border-right: 1px solid #000; width: 30%;" class="bg-gray">Contrato Arrendamiento</td>
                            <td style="border: none; border-right: 1px solid #000; width: 8%; text-align: center;">SI</td>
                            <td class="checkbox-cell" style="width: 5%; border: none; border-right: 1px solid #000;">{{ $mark(stripos((string) ($requisition->leasing_contract ?? ''), 'SI') !== false) }}</td>
                            <td style="border: none; border-right: 1px solid #000; width: 8%; text-align: center;">NO</td>
                            <td class="checkbox-cell" style="width: 5%; border: none; border-right: 1px solid #000;">{{ $mark(! $isBlank && (stripos((string) ($requisition->leasing_contract ?? ''), 'NO') !== false || empty($requisition->leasing_contract))) }}</td>
                            <td style="border: none; width: 44%; text-align: right;">{{ $isBlank ? '$ ' : '$ '.number_format(floatval($requisition->leasing_contract), 0) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- CLIENTE Y PROCESO --}}
        <table class="row-h15">
            <tr class="bg-gray">
                <td colspan="6" class="text-center">ESPECIFICACIONES DEL CLIENTE O PROCESO</td>
            </tr>
            <tr class="value-row">
                <td class="label-cell">NOMBRE DEL CLIENTE:</td>
                <td colspan="5">{{ $cell($isBlank ? null : $requisition->client?->name) }}</td>
            </tr>
            <tr class="value-row">
                <td class="label-cell">CIUDAD DE LABORES:</td>
                <td style="width: 25%;">{{ $cell($isBlank ? null : $requisition->city?->name) }}</td>
                <td class="bg-gray text-center" style="width: 15%;">CLIENTE GRUPO</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(($requisition->clientType?->name ?? null) == 'Grupo') }}</td>
                <td class="bg-gray text-center" style="width: 15%;">CLIENTE EXTERNO</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(! $isBlank && ($requisition->clientType?->name ?? null) != 'Grupo') }}</td>
            </tr>
            <tr class="value-row">
                <td class="label-cell">TIPO DE PROGRAMACION:</td>
                <td colspan="5">{{ $cell($isBlank ? null : $requisition->programmingType?->name) }}</td>
            </tr>
        </table>

        {{-- PERFIL --}}
        <table>
            <tr class="bg-gray">
                <td>Perfil Requerido y Observaciones:</td>
            </tr>
            <tr>
                <td style="height: 120px; vertical-align: top; text-align: justify;">
                    @if ($isBlank)
                        <strong>PERFIL:</strong><br><br>
                        <strong>OBSERVACIONES:</strong>
                    @else
                        <strong>PERFIL:</strong> {{ $requisition->required_profile }}<br><br>
                        <strong>OBSERVACIONES:</strong> {{ $requisition->requester_observation ?? 'Ninguna' }}
                    @endif
                </td>
            </tr>
        </table>

        {{-- DOTACION --}}
        <table>
            <tr class="bg-gray">
                <td colspan="10" class="text-center">DOTACION REQUERIDA:</td>
            </tr>
            <tr>
                <td style="width: 15%;">ADMINISTRATIVA</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->uniform?->name ?? '', 'Administrativa') !== false) }}</td>
                <td style="width: 15%;">GALA</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->uniform?->name ?? '', 'Gala') !== false) }}</td>
                <td style="width: 15%;">OVEROL</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->uniform?->name ?? '', 'Overol') !== false) }}</td>
                <td style="width: 15%;">ESCOLTA</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->uniform?->name ?? '', 'Escolta') !== false) }}</td>
                <td style="width: 15%;">BONO</td>
                <td class="checkbox-cell" style="width: 5%;">{{ $mark(stripos($requisition->uniform?->name ?? '', 'Bono') !== false) }}</td>
            </tr>
        </table>

        {{-- IMPORTANTE --}}
        <div class="important-section">
            <div style="font-weight: bold; text-align: center; text-decoration: underline; margin-bottom: 3px;">IMPORTANTE</div>
            1. El tiempo de contratación a partir de la recepción de la requisición será de 5 días hábiles.<br>
            2. La solicitud de requisición de personal debe ser autorizada por un líder de proceso o jefes de área.<br>
            3. Cuando la solicitud sea realizada para creación de un nuevo cargo, la líder del área que realiza la solicitud debe anexar perfil cargo y firma de Gerencia.<br>
            <span style="color: blue; text-decoration: underline;">4. Una vez se tenga el formato de requisición de personal debidamente diligenciado, si la requisición es de personal administrativo u operativo de la ciudad de Cali o área metropolitana se procederá con la entrega física a Coordinación o Dirección de Gestión Humana, si es operativa a nivel nacional debe enviarse al Coordinador de Programación al correo: coordinador.programacion@sjsp.com.co</span>
        </div>

        {{-- SIGNATURES --}}
        <table>
            <tr>
                <td class="signature-box" style="width: 33%;">
                    <div style="border-top: 1px solid #000; width: 85%; margin: 0 auto;">Firma autorización Líder</div>
                </td>
                <td class="signature-box" style="width: 33%;">
                    <div style="border-top: 1px solid #000; width: 85%; margin: 0 auto;">Aprobación Gerencia (No se requiere en cambios de cargos OP sin afectación de salario)</div>
                </td>
                <td class="signature-box" style="width: 33%;">
                    <div style="border-top: 1px solid #000; width: 85%; margin: 0 auto;">Dirección de Gestión Humana Recibió</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
