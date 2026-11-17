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
 * Excel de Mis solicitudes: una fila por producto de las OC autorizadas del solicitante.
 */
final class MyRequestsOrderLinesExport
{
    /**
     * @param  EloquentCollection<int, PurchaseOrder>  $orders
     */
    public function __construct(
        private readonly EloquentCollection $orders,
        private readonly string $fileName,
        private readonly string $title,
    ) {}

    public function download(): StreamedResponse
    {
        return (new BaseExport($this->rows(), $this->columns(), $this->fileName, $this->title))->download();
    }

    /**
     * @return Collection<int, array{order: PurchaseOrder, line: PurchaseOrderItem|null}>
     */
    public function rows(): Collection
    {
        $this->orders->loadMissing(['items', 'source']);

        return $this->orders->flatMap(function (PurchaseOrder $order): array {
            if ($order->items->isEmpty()) {
                return [['order' => $order, 'line' => null]];
            }

            return $order->items
                ->map(fn (PurchaseOrderItem $line): array => [
                    'order' => $order,
                    'line' => $line,
                ])
                ->all();
        })->values();
    }

    /**
     * @return list<array{key: \Closure, label: string}>
     */
    private function columns(): array
    {
        return [
            ['label' => 'Folio solicitud', 'key' => fn (array $r) => $r['order']->sourceFolio()],
            ['label' => 'Fecha solicitud', 'key' => fn (array $r) => $this->requestDate($r['order'])],
            ['label' => 'N.º OC', 'key' => fn (array $r) => $r['order']->numeroOc],
            ['label' => 'Fecha OC', 'key' => fn (array $r) => $r['order']->fechaImpresa()],
            ['label' => 'Estado OC', 'key' => fn (array $r) => $r['order']->estadoLabel()],
            ['label' => 'Proveedor', 'key' => fn (array $r) => $r['order']->provider_nombre],
            ['label' => 'Artículo / referencia', 'key' => fn (array $r) => $r['line']?->articulo],
            ['label' => 'Descripción', 'key' => fn (array $r) => $r['line']?->descripcion],
            ['label' => 'Cantidad', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->cantidad : null],
            ['label' => 'Valor unidad', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->valor_unidad : null],
            ['label' => 'IVA %', 'key' => fn (array $r) => $r['line']?->iva_porcentaje !== null ? PurchaseTaxRate::formatPorcentaje($r['line']->iva_porcentaje) : ''],
            ['label' => 'IVA', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->iva : null],
            ['label' => 'Valor total línea', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->valor_total : null],
            ['label' => 'Total OC', 'key' => fn (array $r) => (float) $r['order']->total],
        ];
    }

    private function requestDate(PurchaseOrder $order): string
    {
        $source = $order->source;

        return match (true) {
            $source instanceof PurchaseRequest => $source->fecha_solicitud?->format('d/m/Y') ?? '',
            $source instanceof SupplyRequest => $source->created_at?->format('d/m/Y') ?? '',
            default => '',
        };
    }
}
