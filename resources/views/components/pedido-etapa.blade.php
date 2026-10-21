{{-- Pastilla de etapa común del pedido (compra SC- o suministro SU-). --}}
@props(['etapa'])

<span {{ $attributes->class(['status-pill', $etapa->pillClass()]) }}>{{ $etapa->label() }}</span>
