<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseTaxRate;
use App\Models\SupplyRequest;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export del tablero Órdenes: una fila por línea de producto con los datos de su orden de compra.
 * Las órdenes llegan ya filtradas (mismos filtros y búsqueda del listado).
 */
class PurchaseOrderLinesExport
{
    /**
     * @param  EloquentCollection<int, PurchaseOrder>  $orders
     */
    public function __construct(private readonly EloquentCollection $orders) {}

    public function download(): StreamedResponse
    {
        return (new BaseExport(
            $this->rows(),
            $this->columns(),
            'ordenes_compra_productos_'.now()->format('Y-m-d').'.xlsx',
            'Órdenes de compra — productos',
        ))->download();
    }

    /**
     * @return Collection<int, array{order: PurchaseOrder, line: PurchaseOrderItem|null, marca: string}>
     */
    public function rows(): Collection
    {
        $this->orders->loadMissing(['items', 'source', 'evaluation', 'createdBy']);

        // Marca de la línea de origen (solo solicitudes de compra).
        $purchaseSources = new EloquentCollection(
            $this->orders->pluck('source')->filter(fn ($source): bool => $source instanceof PurchaseRequest)->unique('id')->values()->all(),
        );
        $purchaseSources->loadMissing('items');
        $marcas = $purchaseSources->flatMap->items->pluck('marca', 'id');

        return $this->orders->flatMap(function (PurchaseOrder $order) use ($marcas): array {
            if ($order->items->isEmpty()) {
                return [['order' => $order, 'line' => null, 'marca' => '']];
            }

            return $order->items->map(fn (PurchaseOrderItem $line): array => [
                'order' => $order,
                'line' => $line,
                'marca' => $order->source instanceof PurchaseRequest && $line->source_item_id !== null
                    ? (string) ($marcas[$line->source_item_id] ?? '')
                    : '',
            ])->all();
        })->values();
    }

    /**
     * @return list<array{key: \Closure, label: string}>
     */
    private function columns(): array
    {
        return [
            ['label' => 'N.º OC', 'key' => fn (array $r) => $r['order']->numeroOc],
            ['label' => 'Fecha OC', 'key' => fn (array $r) => $r['order']->fechaImpresa()],
            ['label' => 'Estado OC', 'key' => fn (array $r) => $r['order']->estadoLabel()],
            ['label' => 'Origen', 'key' => fn (array $r) => $r['order']->sourceLabel()],
            ['label' => 'Folio origen', 'key' => fn (array $r) => $r['order']->sourceFolio()],
            ['label' => 'Área origen', 'key' => fn (array $r) => $this->sourceArea($r['order'])],
            ['label' => 'N.º cotización', 'key' => fn (array $r) => $r['order']->source instanceof PurchaseRequest ? (string) ($r['order']->source->numero_cotizacion ?? '') : ''],
            ['label' => 'Proveedor', 'key' => fn (array $r) => $r['order']->provider_nombre],
            ['label' => 'Identificación', 'key' => fn (array $r) => $r['order']->provider_identificacion],
            ['label' => 'Contacto', 'key' => fn (array $r) => $r['order']->provider_contacto],
            ['label' => 'Teléfono', 'key' => fn (array $r) => $r['order']->provider_telefono],
            ['label' => 'Correo', 'key' => fn (array $r) => $r['order']->provider_correo],
            ['label' => 'Ciudad proveedor', 'key' => fn (array $r) => $r['order']->provider_ciudad],
            ['label' => 'Forma de pago', 'key' => fn (array $r) => $r['order']->formaPagoImpresa()],
            ['label' => 'Ciudad entrega', 'key' => fn (array $r) => $r['order']->ciudad],
            ['label' => 'Elaborado por', 'key' => fn (array $r) => $r['order']->elaboradoNombre()],
            ['label' => 'Fecha recibido', 'key' => fn (array $r) => $r['order']->evaluation?->fecha_recibido?->format('d/m/Y') ?? ''],
            ['label' => 'Observaciones OC', 'key' => fn (array $r) => $r['order']->observaciones],
            ['label' => 'Total OC', 'key' => fn (array $r) => (float) $r['order']->total],
            ['label' => 'Línea', 'key' => fn (array $r) => $r['line']?->orden],
            ['label' => 'Cantidad', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->cantidad : null],
            ['label' => 'Artículo / referencia', 'key' => fn (array $r) => $r['line']?->articulo],
            ['label' => 'Marca', 'key' => fn (array $r) => $r['marca']],
            ['label' => 'Descripción', 'key' => fn (array $r) => $r['line']?->descripcion],
            ['label' => 'Valor unidad', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->valor_unidad : null],
            ['label' => 'IVA %', 'key' => fn (array $r) => $r['line']?->iva_porcentaje !== null ? PurchaseTaxRate::formatPorcentaje($r['line']->iva_porcentaje) : ''],
            ['label' => 'IVA', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->iva : null],
            ['label' => 'Valor total línea', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->valor_total : null],
        ];
    }

    private function sourceArea(PurchaseOrder $order): string
    {
        $source = $order->source;

        if (! $source instanceof PurchaseRequest && ! $source instanceof SupplyRequest) {
            return '';
        }

        return (string) config('access.areas.'.$source->area_key, $source->area_key);
    }
}
