<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Emisión de Órdenes de Compra (FO-AD-07).
 *
 * El número visible de la OC es el `id` de `purchase_orders` (sin prefijo).
 * Los datos del proveedor se congelan en la cabecera (snapshot) al emitir.
 *
 * Una misma solicitud puede generar **varias** OC (reparto entre proveedores):
 * cada línea del origen tiene un saldo pendiente y la suma de lo ordenado
 * (en todas las OC de ese origen) nunca puede superar lo solicitado.
 */
class PurchaseOrderService
{
    public const SOURCE_PURCHASE = 'purchase_request';

    public const SOURCE_SUPPLY = 'supply_request';

    public const SOURCE_ITEM_PURCHASE = 'purchase_request_item';

    public const SOURCE_ITEM_SUPPLY = 'supply_request_item';

    /**
     * @param  array{provider_id: int|string, fecha?: string|null, ciudad?: string|null, observaciones?: string|null, items: array<int, array<string, mixed>>}  $data
     */
    public function createFromPurchase(PurchaseRequest $purchaseRequest, array $data, User $user): PurchaseOrder
    {
        return $this->create(self::SOURCE_PURCHASE, $purchaseRequest, $data, $user, $this->clientFlagSnapshots($purchaseRequest));
    }

    /**
     * @param  array{provider_id: int|string, fecha?: string|null, ciudad?: string|null, observaciones?: string|null, items: array<int, array<string, mixed>>}  $data
     */
    public function createFromSupply(SupplyRequest $supplyRequest, array $data, User $user): PurchaseOrder
    {
        return $this->create(self::SOURCE_SUPPLY, $supplyRequest, $data, $user, $this->emptyFlagSnapshots());
    }

    /**
     * Líneas del origen con el saldo pendiente de cada una.
     *
     * @return list<array{index: int, source_item_id: int, articulo: string, descripcion: string, cantidad_solicitada: float, cantidad_ordenada: float, cantidad_pendiente: float, valor_unidad: float, iva: float}>
     */
    public function sourceLines(string $sourceType, PurchaseRequest|SupplyRequest $source): array
    {
        return $sourceType === self::SOURCE_PURCHASE
            ? $this->purchaseLines($source)
            : $this->supplyLines($source);
    }

    /**
     * Construye las líneas iniciales (cantidad pendiente) a partir del origen.
     *
     * @return list<array{index: int, source_item_id: int, articulo: string, descripcion: string, cantidad_solicitada: float, cantidad_ordenada: float, cantidad_pendiente: float, valor_unidad: float, iva: float}>
     */
    public function buildLinesFromPurchase(PurchaseRequest $purchaseRequest): array
    {
        return $this->sourceLines(self::SOURCE_PURCHASE, $purchaseRequest);
    }

    /**
     * Construye las líneas iniciales a partir del suministro (usa cantidad autorizada).
     *
     * @return list<array{index: int, source_item_id: int, articulo: string, descripcion: string, cantidad_solicitada: float, cantidad_ordenada: float, cantidad_pendiente: float, valor_unidad: float, iva: float}>
     */
    public function buildLinesFromSupply(SupplyRequest $supplyRequest): array
    {
        return $this->sourceLines(self::SOURCE_SUPPLY, $supplyRequest);
    }

    /**
     * Cantidad ya ordenada por línea de origen (suma de todas las OC del origen).
     *
     * @return array<int, float>
     */
    public function orderedQuantities(string $sourceType, int $sourceId): array
    {
        $sourceItemType = $sourceType === self::SOURCE_PURCHASE
            ? self::SOURCE_ITEM_PURCHASE
            : self::SOURCE_ITEM_SUPPLY;

        return PurchaseOrderItem::query()
            ->where('source_item_type', $sourceItemType)
            ->whereIn('purchase_order_id', PurchaseOrder::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->select('id'))
            ->whereNotNull('source_item_id')
            ->groupBy('source_item_id')
            ->selectRaw('source_item_id, SUM(cantidad) as total_ordenado')
            ->pluck('total_ordenado', 'source_item_id')
            ->map(fn ($total): float => (float) $total)
            ->all();
    }

    /**
     * @param  array{provider_id: int|string, fecha?: string|null, ciudad?: string|null, observaciones?: string|null, items: array<int, array<string, mixed>>}  $data
     * @param  array{asume_cliente: string|null, proyecto_nuevo: string|null, reinversion: string|null, comodato: string|null, activo: string|null}  $extra
     */
    private function create(string $sourceType, PurchaseRequest|SupplyRequest $source, array $data, User $user, array $extra): PurchaseOrder
    {
        // Cerrado es terminal: no se emite otra orden aunque todavía quede saldo.
        if (! $source->puedeEmitirOrden()) {
            throw new InvalidArgumentException('No se puede emitir otra orden: el pedido está cerrado.');
        }

        $provider = PurchaseProvider::query()->findOrFail($data['provider_id']);

        if (! $provider->isActivo()) {
            throw new InvalidArgumentException('El proveedor seleccionado no está activo.');
        }

        $postedItems = array_values($data['items'] ?? []);

        if ($postedItems === []) {
            throw new InvalidArgumentException('La orden de compra debe tener al menos una línea.');
        }

        return DB::transaction(function () use ($sourceType, $source, $data, $user, $extra, $provider, $postedItems): PurchaseOrder {
            $sourceLines = $this->sourceLines($sourceType, $source);
            $ordered = $this->orderedQuantities($sourceType, (int) $source->getKey());

            $order = PurchaseOrder::query()->create([
                'source_type' => $sourceType,
                'source_id' => $source->getKey(),
                'provider_id' => $provider->getKey(),
                'provider_identificacion' => $provider->identificacion,
                'provider_nombre' => $provider->nombre,
                'provider_telefono' => $provider->telefono,
                'provider_correo' => $provider->correo,
                'provider_contacto' => $provider->contacto,
                'provider_direccion' => $provider->direccion,
                'provider_ciudad' => $provider->ciudad,
                'provider_forma_pago' => $provider->forma_pago,
                'provider_dias_pago' => $provider->dias_pago,
                'fecha' => $data['fecha'] ?? now()->toDateString(),
                'ciudad' => $data['ciudad'] ?? $provider->ciudad,
                'asume_cliente' => $extra['asume_cliente'],
                'proyecto_nuevo' => $extra['proyecto_nuevo'],
                'reinversion' => $extra['reinversion'],
                'comodato' => $extra['comodato'],
                'activo' => $extra['activo'],
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => 'emitida',
                'total' => 0,
                'created_by' => $user->getKey(),
            ]);

            $total = 0.0;
            $orden = 1;
            $lineasConCantidad = 0;

            foreach ($sourceLines as $line) {
                $posted = $postedItems[$line['index']] ?? null;

                if ($posted === null) {
                    continue;
                }

                $cantidad = (float) ($posted['cantidad'] ?? 0);

                if ($cantidad <= 0) {
                    continue;
                }

                $pendiente = $this->pendingQuantity($line, $ordered);

                if ($cantidad - $pendiente > 0.001) {
                    throw new InvalidArgumentException(sprintf(
                        'La cantidad de "%s" supera el saldo pendiente (%s).',
                        $line['descripcion'] !== '' ? $line['descripcion'] : 'la línea',
                        $this->formatQuantity($pendiente),
                    ));
                }

                $valorUnidad = (float) ($posted['valor_unidad'] ?? 0);
                $iva = (float) ($posted['iva'] ?? 0);
                $valorTotal = round(($cantidad * $valorUnidad) + $iva, 2);

                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $order->getKey(),
                    'orden' => $orden++,
                    'cantidad' => $cantidad,
                    'articulo' => $this->nullableString($posted['articulo'] ?? $line['articulo']),
                    'descripcion' => $this->nullableString($posted['descripcion'] ?? $line['descripcion']),
                    'valor_unidad' => $valorUnidad,
                    'iva' => $iva,
                    'valor_total' => $valorTotal,
                    'source_item_type' => $sourceType === self::SOURCE_PURCHASE
                        ? self::SOURCE_ITEM_PURCHASE
                        : self::SOURCE_ITEM_SUPPLY,
                    'source_item_id' => $line['source_item_id'],
                ]);

                $total += $valorTotal;
                $lineasConCantidad++;
            }

            if ($lineasConCantidad === 0) {
                throw new InvalidArgumentException('La orden de compra debe tener al menos una línea con cantidad mayor a cero.');
            }

            $order->update(['total' => round($total, 2)]);

            return $order->fresh(['provider', 'items', 'source']);
        });
    }

    /**
     * @return list<array{index: int, source_item_id: int, articulo: string, descripcion: string, cantidad_solicitada: float, cantidad_ordenada: float, cantidad_pendiente: float, valor_unidad: float, iva: float}>
     */
    private function purchaseLines(PurchaseRequest $source): array
    {
        $ordered = $this->orderedQuantities(self::SOURCE_PURCHASE, (int) $source->getKey());

        return $source->items
            ->values()
            ->map(function ($item, int $index) use ($ordered): array {
                $solicitada = (float) $item->cantidad;
                $ordenada = (float) ($ordered[$item->getKey()] ?? 0);

                return [
                    'index' => $index,
                    'source_item_id' => (int) $item->getKey(),
                    'articulo' => (string) ($item->referencia ?? ''),
                    'descripcion' => (string) ($item->descripcion ?? ''),
                    'cantidad_solicitada' => $solicitada,
                    'cantidad_ordenada' => $ordenada,
                    'cantidad_pendiente' => max(0.0, round($solicitada - $ordenada, 2)),
                    'valor_unidad' => 0.0,
                    'iva' => 0.0,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{index: int, source_item_id: int, articulo: string, descripcion: string, cantidad_solicitada: float, cantidad_ordenada: float, cantidad_pendiente: float, valor_unidad: float, iva: float}>
     */
    private function supplyLines(SupplyRequest $source): array
    {
        $ordered = $this->orderedQuantities(self::SOURCE_SUPPLY, (int) $source->getKey());

        return $source->items
            ->filter(fn ($item): bool => (float) ($item->approved_quantity ?? $item->requested_quantity ?? 0) > 0)
            ->values()
            ->map(function ($item, int $index) use ($ordered): array {
                $solicitada = (float) ($item->approved_quantity ?? $item->requested_quantity ?? 0);
                $ordenada = (float) ($ordered[$item->getKey()] ?? 0);

                return [
                    'index' => $index,
                    'source_item_id' => (int) $item->getKey(),
                    'articulo' => (string) $item->referenceLabel(),
                    'descripcion' => (string) $item->displayName(),
                    'cantidad_solicitada' => $solicitada,
                    'cantidad_ordenada' => $ordenada,
                    'cantidad_pendiente' => max(0.0, round($solicitada - $ordenada, 2)),
                    // Costo histórico del ítem: solo precarga la OC. Procesar el suministro no lo borra.
                    'valor_unidad' => (float) ($item->unit_cost ?? 0),
                    'iva' => 0.0,
                ];
            })
            ->all();
    }

    /**
     * @param  array{index: int, source_item_id: int, articulo: string, descripcion: string, cantidad_solicitada: float, cantidad_ordenada: float, cantidad_pendiente: float, valor_unidad: float, iva: float}  $line
     * @param  array<int, float>  $ordered
     */
    private function pendingQuantity(array $line, array $ordered): float
    {
        $ordenada = (float) ($ordered[$line['source_item_id']] ?? $line['cantidad_ordenada']);

        return max(0.0, round($line['cantidad_solicitada'] - $ordenada, 2));
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');
    }

    /**
     * @return array{asume_cliente: string|null, proyecto_nuevo: string|null, reinversion: string|null, comodato: string|null, activo: string|null}
     */
    private function clientFlagSnapshots(PurchaseRequest $purchaseRequest): array
    {
        return [
            'asume_cliente' => $this->snapshotSiNo($purchaseRequest->asume_cliente),
            'proyecto_nuevo' => $this->snapshotSiNo($purchaseRequest->proyecto_nuevo),
            'reinversion' => $this->snapshotSiNo($purchaseRequest->reinversion),
            'comodato' => $this->snapshotSiNo($purchaseRequest->comodato),
            'activo' => $this->snapshotSiNo($purchaseRequest->activo),
        ];
    }

    /**
     * @return array{asume_cliente: null, proyecto_nuevo: null, reinversion: null, comodato: null, activo: null}
     */
    private function emptyFlagSnapshots(): array
    {
        return [
            'asume_cliente' => null,
            'proyecto_nuevo' => null,
            'reinversion' => null,
            'comodato' => null,
            'activo' => null,
        ];
    }

    private function snapshotSiNo(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value ? 'SI' : 'NO';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
