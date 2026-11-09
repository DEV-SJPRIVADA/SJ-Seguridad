<?php

namespace App\Services\Supplies;

use App\Models\PurchaseOrder;
use App\Models\SupplyRequest;
use App\Models\SupplyRequestItem;
use App\Models\SupplySite;
use App\Services\PurchaseRequests\PurchaseOrderService;
use App\Support\DisplayDate;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplySeguimientoService
{
    public const AUTHORIZED_STATUSES = ['aprobada_calidad', 'en_compras', 'completada'];

    /** @var array<int, string> */
    public const MONTH_LABELS = [
        1 => 'Ene',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dic',
    ];

    /** @var array<int, string> */
    public const MONTH_NAMES = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    /**
     * @return array{year: int, month: ?int, area_key: ?string, sede_id: ?int, product: ?string}
     */
    public function parseFilters(array $input): array
    {
        $year = (int) ($input['year'] ?? now()->year);
        $minYear = $this->minYear();
        $maxYear = (int) now()->year;

        if ($year < $minYear || $year > $maxYear) {
            $year = $maxYear;
        }

        $month = filled($input['month'] ?? null) ? (int) $input['month'] : null;
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        $areaKey = filled($input['area_key'] ?? null) ? (string) $input['area_key'] : null;
        $areas = array_keys(config('access.areas', []));
        if ($areaKey !== null && ! in_array($areaKey, $areas, true)) {
            $areaKey = null;
        }

        $sedeId = filled($input['sede_id'] ?? null) ? (int) $input['sede_id'] : null;
        if ($sedeId !== null && $sedeId < 1) {
            $sedeId = null;
        }

        $product = filled($input['product'] ?? null) ? (string) $input['product'] : null;

        return [
            'year' => $year,
            'month' => $month,
            'area_key' => $areaKey,
            'sede_id' => $sedeId,
            'product' => $product,
        ];
    }

    public function minYear(): int
    {
        $earliest = SupplyRequest::query()->min('created_at');

        if ($earliest === null) {
            return (int) now()->year;
        }

        return (int) Carbon::parse($earliest)->year;
    }

    /**
     * @return array<string, string>
     */
    public function yearOptions(): array
    {
        $options = [];
        foreach (range($this->minYear(), (int) now()->year) as $year) {
            $options[(string) $year] = (string) $year;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function monthOptions(): array
    {
        $options = [];
        foreach (self::MONTH_NAMES as $month => $label) {
            $options[(string) $month] = $label;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function areaOptions(): array
    {
        return config('access.areas', []);
    }

    /**
     * @return array<int, string>
     */
    public function sedeOptions(): array
    {
        return SupplySite::query()
            ->ordered()
            ->get()
            ->mapWithKeys(fn (SupplySite $site): array => [
                $site->id => trim($site->city.' · '.$site->utilization),
            ])
            ->all();
    }

    /**
     * Productos del año (y filtros de área/sede) para el selector. No aplica el filtro de producto.
     *
     * @param  array{year: int, area_key: ?string, sede_id: ?int, product: ?string}  $filters
     * @return array<string, string>
     */
    public function productOptions(array $filters): array
    {
        $base = $filters;
        $base['product'] = null;
        $options = [];

        foreach ($this->authorizedItems($base) as $item) {
            $options[$this->productKey($item)] = $this->productName($item);
        }

        $purchased = $this->orderItemQuery($base, receivedOnly: true)
            ->whereYear('purchase_order_evaluations.fecha_recibido', $filters['year'])
            ->get();

        foreach ($purchased as $row) {
            $options[$this->productKeyFromOrderRow($row)] = $this->productNameFromOrderRow($row);
        }

        natcasesort($options);

        return $options;
    }

    /**
     * Matriz productos × meses: inventario (último declarado), solicitado y autorizado.
     *
     * @param  array{year: int, area_key: ?string, sede_id: ?int, product: ?string}  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<int, array{inventory: ?int, requested: int, authorized: int}>, year_totals: array{requested: int, authorized: int}}
     */
    public function historialMatrix(array $filters): array
    {
        $items = $this->authorizedItems($filters);
        $grouped = [];

        foreach ($items as $item) {
            $key = $this->productKey($item);
            $month = (int) Carbon::parse($item->request_created_at)->month;
            $grouped[$key] ??= [
                'key' => $key,
                'name' => $this->productName($item),
                'is_custom' => (bool) $item->is_not_in_catalog,
                'months' => $this->emptyHistorialMonths(),
            ];

            $cell = &$grouped[$key]['months'][$month];
            $cell['requested'] += (int) $item->requested_quantity;
            $cell['authorized'] += (int) ($item->approved_quantity ?? 0);
            $cell['inventory'] = (int) $item->current_inventory;
            $cell['inventory_at'] = (string) $item->request_created_at;
            unset($cell);
        }

        $rows = collect($grouped)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (array $row): array {
                $requested = 0;
                $authorized = 0;
                $lastInventory = null;
                $lastAt = null;

                foreach ($row['months'] as $month => $cell) {
                    $requested += $cell['requested'];
                    $authorized += $cell['authorized'];
                    if ($cell['inventory'] !== null && ($lastAt === null || $cell['inventory_at'] >= $lastAt)) {
                        $lastInventory = $cell['inventory'];
                        $lastAt = $cell['inventory_at'];
                    }
                    unset($row['months'][$month]['inventory_at']);
                }

                $row['year_requested'] = $requested;
                $row['year_authorized'] = $authorized;
                $row['year_inventory'] = $lastInventory;

                return $row;
            })
            ->all();

        $totals = $this->emptyHistorialMonths();
        $yearRequested = 0;
        $yearAuthorized = 0;

        foreach ($rows as $row) {
            foreach ($row['months'] as $month => $cell) {
                $totals[$month]['requested'] += $cell['requested'];
                $totals[$month]['authorized'] += $cell['authorized'];
            }
            $yearRequested += $row['year_requested'];
            $yearAuthorized += $row['year_authorized'];
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'year_totals' => [
                'requested' => $yearRequested,
                'authorized' => $yearAuthorized,
            ],
        ];
    }

    /**
     * Órdenes de compra del año para un producto (incluye emitidas; excluye anuladas).
     *
     * @param  array{year: int, month: ?int, area_key: ?string, sede_id: ?int, product: ?string}  $filters
     * @return list<array{numero_oc: int, fecha: string, folio: string, cantidad: float, valor: float, estado: string}>
     */
    public function productOrders(string $productKey, array $filters): array
    {
        $query = $this->orderItemQuery($filters, receivedOnly: false)
            ->whereYear('purchase_orders.fecha', $filters['year']);

        if (($filters['month'] ?? null) !== null) {
            $query->whereMonth('purchase_orders.fecha', $filters['month']);
        }

        $this->constrainProduct($query, $productKey);

        return $query
            ->orderBy('purchase_orders.fecha')
            ->orderBy('purchase_orders.id')
            ->get()
            ->map(function (object $row): array {
                $recibida = filled($row->fecha_recibido ?? null);

                return [
                    'numero_oc' => (int) $row->order_id,
                    'fecha' => DisplayDate::date($row->fecha),
                    'folio' => 'SU-'.str_pad((string) $row->source_id, 4, '0', STR_PAD_LEFT),
                    'cantidad' => (float) $row->cantidad,
                    'valor' => (float) $row->valor_total,
                    'estado' => $recibida ? 'Recibida' : (string) (config('purchase-orders.estados')[$row->estado] ?? $row->estado),
                ];
            })
            ->all();
    }

    public function productNameByKey(string $productKey, array $filters): ?string
    {
        foreach ($this->historialMatrix($filters)['rows'] as $row) {
            if ($row['key'] === $productKey) {
                return (string) $row['name'];
            }
        }

        $item = $this->authorizedItems($filters)->first(fn ($row): bool => $this->productKey($row) === $productKey);

        return $item ? $this->productName($item) : null;
    }

    /**
     * Matriz de gasto: cantidad solicitada vs comprada (OC recibidas) y valor con IVA.
     *
     * @param  array{year: int, area_key: ?string, sede_id: ?int, product: ?string}  $filters
     * @return array{rows: list<array<string, mixed>>, totals: array<int, array{requested: int, purchased: float, value: float}>, year_totals: array{requested: int, purchased: float, value: float}}
     */
    public function gastoMatrix(array $filters): array
    {
        $requestedByKey = [];
        foreach ($this->authorizedItems($filters) as $item) {
            $key = $this->productKey($item);
            $month = (int) Carbon::parse($item->request_created_at)->month;
            $requestedByKey[$key] ??= [
                'key' => $key,
                'name' => $this->productName($item),
                'is_custom' => (bool) $item->is_not_in_catalog,
                'months' => $this->emptyGastoMonths(),
            ];
            $requestedByKey[$key]['months'][$month]['requested'] += (int) $item->requested_quantity;
        }

        $purchased = $this->orderItemQuery($filters, receivedOnly: true)
            ->whereYear('purchase_order_evaluations.fecha_recibido', $filters['year'])
            ->get();

        foreach ($purchased as $row) {
            $key = $this->productKeyFromOrderRow($row);
            $month = (int) Carbon::parse($row->fecha_recibido)->month;
            $requestedByKey[$key] ??= [
                'key' => $key,
                'name' => $this->productNameFromOrderRow($row),
                'is_custom' => (bool) $row->is_not_in_catalog,
                'months' => $this->emptyGastoMonths(),
            ];
            $requestedByKey[$key]['months'][$month]['purchased'] += (float) $row->cantidad;
            $requestedByKey[$key]['months'][$month]['value'] += (float) $row->valor_total;
        }

        $rows = collect($requestedByKey)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function (array $row): array {
                $requested = 0;
                $purchased = 0.0;
                $value = 0.0;
                foreach ($row['months'] as $cell) {
                    $requested += $cell['requested'];
                    $purchased += $cell['purchased'];
                    $value += $cell['value'];
                }
                $row['year_requested'] = $requested;
                $row['year_purchased'] = $purchased;
                $row['year_value'] = $value;

                return $row;
            })
            ->all();

        $totals = $this->emptyGastoMonths();
        $yearRequested = 0;
        $yearPurchased = 0.0;
        $yearValue = 0.0;

        foreach ($rows as $row) {
            foreach ($row['months'] as $month => $cell) {
                $totals[$month]['requested'] += $cell['requested'];
                $totals[$month]['purchased'] += $cell['purchased'];
                $totals[$month]['value'] += $cell['value'];
            }
            $yearRequested += $row['year_requested'];
            $yearPurchased += $row['year_purchased'];
            $yearValue += $row['year_value'];
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'year_totals' => [
                'requested' => $yearRequested,
                'purchased' => $yearPurchased,
                'value' => $yearValue,
            ],
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function authorizedItems(array $filters): Collection
    {
        $query = SupplyRequestItem::query()
            ->select([
                'supply_request_items.id',
                'supply_request_items.supply_product_id',
                'supply_request_items.custom_product_name',
                'supply_request_items.is_not_in_catalog',
                'supply_request_items.current_inventory',
                'supply_request_items.requested_quantity',
                'supply_request_items.approved_quantity',
                'supply_requests.created_at as request_created_at',
                'supply_products.name as catalog_name',
            ])
            ->join('supply_requests', 'supply_requests.id', '=', 'supply_request_items.supply_request_id')
            ->leftJoin('supply_products', 'supply_products.id', '=', 'supply_request_items.supply_product_id')
            ->whereIn('supply_requests.status', self::AUTHORIZED_STATUSES)
            ->whereYear('supply_requests.created_at', $filters['year'])
            ->when($filters['area_key'] !== null, fn ($q) => $q->where('supply_requests.area_key', $filters['area_key']))
            ->when($filters['sede_id'] !== null, fn ($q) => $q->where('supply_requests.sede_id', $filters['sede_id']))
            ->when(filled($filters['product'] ?? null), function ($q) use ($filters): void {
                $this->constrainProduct($q, (string) $filters['product']);
            })
            ->orderBy('supply_requests.created_at')
            ->orderBy('supply_request_items.id');

        return $query->get();
    }

    /**
     * @return Builder|\Illuminate\Database\Eloquent\Builder
     */
    private function orderItemQuery(array $filters, bool $receivedOnly)
    {
        $query = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('supply_request_items', function ($join): void {
                $join->on('supply_request_items.id', '=', 'purchase_order_items.source_item_id')
                    ->where('purchase_order_items.source_item_type', '=', PurchaseOrderService::SOURCE_ITEM_SUPPLY);
            })
            ->join('supply_requests', 'supply_requests.id', '=', 'supply_request_items.supply_request_id')
            ->leftJoin('supply_products', 'supply_products.id', '=', 'supply_request_items.supply_product_id')
            ->leftJoin('purchase_order_evaluations', 'purchase_order_evaluations.purchase_order_id', '=', 'purchase_orders.id')
            ->where('purchase_orders.source_type', PurchaseOrderService::SOURCE_SUPPLY)
            ->where('purchase_orders.estado', '!=', PurchaseOrder::ESTADO_ANULADA)
            ->when($filters['area_key'] !== null, fn ($q) => $q->where('supply_requests.area_key', $filters['area_key']))
            ->when($filters['sede_id'] !== null, fn ($q) => $q->where('supply_requests.sede_id', $filters['sede_id']))
            ->when(filled($filters['product'] ?? null), function ($q) use ($filters): void {
                $this->constrainProduct($q, (string) $filters['product']);
            })
            ->select([
                'purchase_orders.id as order_id',
                'purchase_orders.fecha',
                'purchase_orders.estado',
                'purchase_orders.source_id',
                'purchase_order_items.cantidad',
                'purchase_order_items.valor_total',
                'purchase_order_evaluations.fecha_recibido',
                'supply_request_items.supply_product_id',
                'supply_request_items.custom_product_name',
                'supply_request_items.is_not_in_catalog',
                'supply_products.name as catalog_name',
            ]);

        if ($receivedOnly) {
            $query->whereNotNull('purchase_order_evaluations.fecha_recibido');
        }

        return $query;
    }

    private function constrainProduct($query, string $productKey): void
    {
        if (str_starts_with($productKey, 'p:')) {
            $id = (int) substr($productKey, 2);
            $query->where('supply_request_items.supply_product_id', $id)
                ->where('supply_request_items.is_not_in_catalog', false);

            return;
        }

        if (str_starts_with($productKey, 'c:')) {
            $name = $this->decodeCustomKey($productKey);
            if ($name === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('supply_request_items.is_not_in_catalog', true)
                ->where('supply_request_items.custom_product_name', $name);
        }
    }

    private function productKey(object $item): string
    {
        if ($item->is_not_in_catalog || ! $item->supply_product_id) {
            return 'c:'.$this->encodeCustomKey((string) ($item->custom_product_name ?? ''));
        }

        return 'p:'.(int) $item->supply_product_id;
    }

    private function productKeyFromOrderRow(object $row): string
    {
        return $this->productKey($row);
    }

    private function productName(object $item): string
    {
        if ($item->is_not_in_catalog || filled($item->custom_product_name ?? null)) {
            return (string) ($item->custom_product_name ?: $item->catalog_name ?: 'Producto fuera de catálogo');
        }

        return (string) ($item->catalog_name ?: 'Producto');
    }

    private function productNameFromOrderRow(object $row): string
    {
        return $this->productName($row);
    }

    private function encodeCustomKey(string $name): string
    {
        $normalized = mb_strtoupper(trim($name), 'UTF-8');

        return rtrim(strtr(base64_encode($normalized), '+/', '-_'), '=');
    }

    private function decodeCustomKey(string $productKey): ?string
    {
        $payload = substr($productKey, 2);
        $base64 = strtr($payload, '-_', '+/');
        $pad = strlen($base64) % 4;
        if ($pad > 0) {
            $base64 .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($base64, true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * @return array<int, array{inventory: ?int, requested: int, authorized: int, inventory_at: ?string}>
     */
    private function emptyHistorialMonths(): array
    {
        $months = [];
        foreach (array_keys(self::MONTH_LABELS) as $month) {
            $months[$month] = [
                'inventory' => null,
                'requested' => 0,
                'authorized' => 0,
                'inventory_at' => null,
            ];
        }

        return $months;
    }

    /**
     * @return array<int, array{requested: int, purchased: float, value: float}>
     */
    private function emptyGastoMonths(): array
    {
        $months = [];
        foreach (array_keys(self::MONTH_LABELS) as $month) {
            $months[$month] = [
                'requested' => 0,
                'purchased' => 0.0,
                'value' => 0.0,
            ];
        }

        return $months;
    }
}
