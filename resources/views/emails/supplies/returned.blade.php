@component('mail::message')
# Compras devolvió su pedido de insumos

Su pedido **{{ $supplyRequest->folioVisible() }}** fue **devuelto por Compras** para que lo corrija.

**Motivo:**

{{ $motivo }}

Corríjalo desde **Mis solicitudes** de Suministros y envíelo de nuevo. Al reenviarlo, Calidad debe volver a aprobarlo.

@component('mail::button', ['url' => route('supplies.show', ['module' => $supplyRequest->area_key, 'supply_request' => $supplyRequest->id])])
Ver pedido
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent
