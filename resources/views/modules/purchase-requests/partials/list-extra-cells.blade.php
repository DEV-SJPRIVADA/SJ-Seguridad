{{-- Celdas de listado: número de cotización, marcas de los productos y comentarios del solicitante (recortados; texto completo en title). --}}
@php
    $marcas = $purchaseRequest?->marcasResumen() ?? '';
    $comentarios = trim((string) ($purchaseRequest?->comentarios_solicitante ?? ''));
@endphp
<td class="text-center">{{ $purchaseRequest?->numero_cotizacion ?: '—' }}</td>
<td @if ($marcas !== '') title="{{ $marcas }}" @endif>{{ $marcas !== '' ? \Illuminate\Support\Str::limit($marcas, 40) : '—' }}</td>
<td @if ($comentarios !== '') title="{{ $comentarios }}" @endif>{{ $comentarios !== '' ? \Illuminate\Support\Str::limit($comentarios, 60) : '—' }}</td>
