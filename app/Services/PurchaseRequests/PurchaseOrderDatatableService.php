<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Support\ColombianCurrency;
use App\Support\DisplayDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Listado server-side de las órdenes de compra (FO-AD-07).
 *
 * Una misma solicitud puede tener varias OC (reparto entre proveedores), por
 * lo que el listado muestra el origen de cada orden.
 */
final class PurchaseOrderDatatableService
{
    public const TIPO_TODOS = 'todos';

    public const ESTADO_TODOS = 'todos';

    /**
     * @var array<int, string>
     */
    private const ORDERABLE_COLUMNS = [
        0 => 'id',
        1 => 'fecha',
        2 => 'provider_nombre',
        3 => 'provider_identificacion',
        4 => 'source_type',
        5 => 'ciudad',
        7 => 'total',
        8 => 'estado',
    ];

    /**
     * @return array{estado: string, tipo: string, proveedor_id: int|null, desde: string|null, hasta: string|null}
     */
    public function filtersFromRequest(Request $request): array
    {
        return $this->filtersFromInput($request->all());
    }

    /**
     * @return array{estado: string, tipo: string, proveedor_id: int|null, desde: string|null, hasta: string|null}
     */
    public function filtersFromQuery(Request $request): array
    {
        return $this->filtersFromInput($request->query());
    }

    /**
     * @param  array{estado: string, tipo: string, proveedor_id: int|null, desde: string|null, hasta: string|null}  $filters
     * @return array<string, string>
     */
    public function filtersToQuery(array $filters): array
    {
        $query = [];

        if ($filters['estado'] !== self::ESTADO_TODOS) {
            $query['estado'] = $filters['estado'];
        }

        if ($filters['tipo'] !== self::TIPO_TODOS) {
            $query['tipo'] = $filters['tipo'];
        }

        if ($filters['proveedor_id'] !== null) {
            $query['proveedor_id'] = (string) $filters['proveedor_id'];
        }

        if ($filters['desde'] !== null) {
            $query['desde'] = $filters['desde'];
        }

        if ($filters['hasta'] !== null) {
            $query['hasta'] = $filters['hasta'];
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{estado: string, tipo: string, proveedor_id: int|null, desde: string|null, hasta: string|null}
     */
    private function filtersFromInput(array $input): array
    {
        $estado = (string) ($input['estado'] ?? self::ESTADO_TODOS);

        if (! in_array($estado, [...array_keys((array) config('purchase-orders.estados', [])), self::ESTADO_TODOS], true)) {
            $estado = self::ESTADO_TODOS;
        }

        $tipo = (string) ($input['tipo'] ?? self::TIPO_TODOS);

        if (! in_array($tipo, ['compra', 'suministro', self::TIPO_TODOS], true)) {
            $tipo = self::TIPO_TODOS;
        }

        $proveedorId = $input['proveedor_id'] ?? null;
        $proveedorId = is_numeric($proveedorId) && (int) $proveedorId > 0 ? (int) $proveedorId : null;

        return [
            'estado' => $estado,
            'tipo' => $tipo,
            'proveedor_id' => $proveedorId,
            'desde' => $this->dateOrNull($input['desde'] ?? null),
            'hasta' => $this->dateOrNull($input['hasta'] ?? null),
        ];
    }

    private function dateOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    /**
     * @param  array{estado: string, tipo: string, proveedor_id: int|null, desde: string|null, hasta: string|null}  $filters
     * @return Builder<PurchaseOrder>
     */
    public function filteredQuery(array $filters): Builder
    {
        return PurchaseOrder::query()
            // `evaluation` alimenta la columna «Recibido» (fecha de recibido de la OC).
            ->with(['source', 'evaluation'])
            ->withCount('items')
            ->when(
                $filters['estado'] !== self::ESTADO_TODOS,
                fn (Builder $query): Builder => $query->where('estado', $filters['estado']),
            )
            ->when($filters['tipo'] === 'compra', fn (Builder $query): Builder => $query->where('source_type', PurchaseOrderService::SOURCE_PURCHASE))
            ->when($filters['tipo'] === 'suministro', fn (Builder $query): Builder => $query->where('source_type', PurchaseOrderService::SOURCE_SUPPLY))
            ->when($filters['proveedor_id'] !== null, fn (Builder $query): Builder => $query->where('provider_id', $filters['proveedor_id']))
            ->when($filters['desde'] !== null, fn (Builder $query): Builder => $query->where('fecha', '>=', $filters['desde']))
            ->when($filters['hasta'] !== null, fn (Builder $query): Builder => $query->where('fecha', '<=', $filters['hasta']));
    }

    /**
     * @param  array{estado: string, tipo: string, proveedor_id: int|null, desde: string|null, hasta: string|null}  $filters
     */
    public function respond(Request $request, array $filters): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);

        $baseQuery = $this->filteredQuery($filters);
        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;
        $this->applyGlobalSearch($query, $request);
        $recordsFiltered = (clone $query)->count();

        $this->applyOrdering($query, $request);

        if ($length !== -1) {
            $query->skip($start)->take(max(1, min(100, $length)));
        } else {
            $query->take(100);
        }

        $rows = $query->get()
            ->map(fn (PurchaseOrder $order): array => $this->formatRow($order))
            ->values()
            ->all();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * @param  Builder<PurchaseOrder>  $query
     */
    private function applyGlobalSearch(Builder $query, Request $request): void
    {
        $this->applySearchTerm($query, (string) data_get($request->all(), 'search.value', ''));
    }

    /**
     * Búsqueda del listado sobre lo que se ve en la tabla: N.º OC, proveedor,
     * identificación, ciudad, estado, total y consecutivo de origen (SC- / SU-).
     * Varias palabras deben cumplirse todas. También la usa el export (`q`).
     *
     * @param  Builder<PurchaseOrder>  $query
     */
    public function applySearchTerm(Builder $query, string $search): void
    {
        $search = trim($search);

        if ($search === '') {
            return;
        }

        $words = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $query->where(function (Builder $outer) use ($words): void {
            foreach ($words as $word) {
                $outer->where(function (Builder $inner) use ($word): void {
                    $this->matchSearchWord($inner, $word);
                });
            }
        });
    }

    /**
     * Una palabra del buscador: coincide si aparece en cualquier columna visible.
     *
     * @param  Builder<PurchaseOrder>  $inner
     */
    private function matchSearchWord(Builder $inner, string $word): void
    {
        $term = '%'.$this->escapeLike($word).'%';

        $inner->where('provider_nombre', 'like', $term)
            ->orWhere('provider_identificacion', 'like', $term)
            ->orWhere('observaciones', 'like', $term)
            ->orWhere('ciudad', 'like', $term)
            ->orWhere('estado', 'like', $term)
            ->orWhereRaw('CAST(id AS CHAR) like ?', [$term]);

        $amount = str_replace([',', ' '], '', $word);

        if ($amount !== '' && preg_match('/^\d+(\.\d+)?$/', $amount) === 1) {
            $inner->orWhere('total', 'like', '%'.$this->escapeLike($amount).'%');
        }

        foreach ($this->estadoCodesMatching($word) as $code) {
            $inner->orWhere('estado', $code);
        }

        $this->matchOriginWord($inner, $word);
    }

    /**
     * Consecutivo de origen: SC-0045, SU-0012, 0045 o 45 (sin ceros).
     *
     * @param  Builder<PurchaseOrder>  $inner
     */
    private function matchOriginWord(Builder $inner, string $word): void
    {
        $upper = mb_strtoupper($word);
        $prefix = null;
        $digits = null;

        if (preg_match('/^(SC|SU)[\s\-]*(\d+)$/u', $upper, $matches) === 1) {
            $prefix = $matches[1];
            $digits = $matches[2];
        } elseif (ctype_digit($word)) {
            $digits = $word;
        }

        if ($digits === null || (int) $digits <= 0) {
            return;
        }

        if ($prefix !== 'SU') {
            $this->orWhereSourceFolio($inner, PurchaseOrderService::SOURCE_PURCHASE, PurchaseRequest::query(), 'numero_solicitud', $digits);
        }

        if ($prefix !== 'SC') {
            $this->orWhereSourceFolio($inner, PurchaseOrderService::SOURCE_SUPPLY, SupplyRequest::query(), 'id', $digits);
        }
    }

    /**
     * @param  Builder<PurchaseOrder>  $inner
     * @param  Builder<PurchaseRequest>|Builder<SupplyRequest>  $sources
     */
    private function orWhereSourceFolio(Builder $inner, string $sourceType, Builder $sources, string $column, string $digits): void
    {
        $pad = $this->paddedFolioSql($column);
        $asInt = (int) $digits;

        $ids = $sources->select('id')->where(function (Builder $query) use ($column, $pad, $digits, $asInt): void {
            if (str_starts_with($digits, '0') || strlen($digits) >= 4) {
                $query->whereRaw($pad.' like ?', [$digits.'%']);

                return;
            }

            $query->where($column, $asInt);
        });

        $inner->orWhere(function (Builder $origin) use ($sourceType, $ids): void {
            $origin->where('source_type', $sourceType)
                ->whereIn('source_id', $ids);
        });
    }

    /**
     * Mismo relleno que folioVisible(): 4 dígitos, sin recortar si el número es más largo.
     */
    private function paddedFolioSql(string $column): string
    {
        $driver = PurchaseOrder::query()->getConnection()->getDriverName();

        return $driver === 'sqlite'
            ? "printf('%04d', {$column})"
            : "LPAD({$column}, 4, '0')";
    }

    /**
     * @return list<string>
     */
    private function estadoCodesMatching(string $word): array
    {
        $needle = $this->fold($word);

        if (mb_strlen($needle) < 3) {
            return [];
        }

        $codes = [];

        foreach ((array) config('purchase-orders.estados', []) as $code => $label) {
            $foldedLabel = $this->fold((string) $label);
            $foldedCode = $this->fold((string) $code);

            if (str_contains($foldedLabel, $needle) || str_contains($foldedCode, $needle)) {
                $codes[] = (string) $code;
            }
        }

        return $codes;
    }

    private function fold(string $value): string
    {
        $value = mb_strtolower($value);

        return strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * @param  Builder<PurchaseOrder>  $query
     */
    private function applyOrdering(Builder $query, Request $request): void
    {
        $columnIndex = (int) data_get($request->all(), 'order.0.column', 1);
        $direction = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc'));
        $column = self::ORDERABLE_COLUMNS[$columnIndex] ?? 'fecha';

        $query->orderBy($column, $direction === 'asc' ? 'asc' : 'desc');
    }

    /**
     * @return array<int, string>
     */
    private function formatRow(PurchaseOrder $order): array
    {
        return [
            (string) $order->numeroOc,
            e((string) $order->fecha),
            e((string) $order->provider_nombre),
            e((string) $order->provider_identificacion),
            e($this->originLabel($order)),
            e((string) ($order->ciudad ?? '—')),
            (string) (int) $order->items_count,
            ColombianCurrency::format($order->total),
            $this->estadoPill($order),
            $this->recibidoPill($order),
            $this->rowActions($order),
        ];
    }

    /**
     * Columna «Recibido»: indica si la OC ya tiene fecha de recibido (evaluación post-entrega).
     */
    private function recibidoPill(PurchaseOrder $order): string
    {
        $fecha = $order->evaluation?->fecha_recibido;

        if ($fecha === null) {
            return '<span class="status-pill status-pill--muted">No recibido</span>';
        }

        return sprintf(
            '<span class="status-pill status-pill--success" title="Fecha de recibido">Recibido %s</span>',
            e(DisplayDate::date($fecha)),
        );
    }

    /**
     * Columna Origen: solo el consecutivo visible (SC-0001 / SU-0001).
     */
    private function originLabel(PurchaseOrder $order): string
    {
        return $order->sourceFolio();
    }

    private function estadoPill(PurchaseOrder $order): string
    {
        $pill = match ($order->estado) {
            PurchaseOrder::ESTADO_EMITIDA => 'status-pill--success',
            PurchaseOrder::ESTADO_PENDIENTE_APROBACION => 'status-pill--warning',
            PurchaseOrder::ESTADO_ANULACION_PENDIENTE => 'status-pill--warning',
            PurchaseOrder::ESTADO_ANULADA => 'status-pill--danger',
            default => 'status-pill--muted',
        };

        return sprintf('<span class="status-pill %s">%s</span>', $pill, e($order->estadoLabel()));
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function estadoOptions(): array
    {
        return [
            ['value' => self::ESTADO_TODOS, 'label' => 'Todas'],
            ['value' => PurchaseOrder::ESTADO_PENDIENTE_APROBACION, 'label' => 'Pendientes de autorización'],
            ['value' => PurchaseOrder::ESTADO_EMITIDA, 'label' => 'Emitidas'],
            ['value' => PurchaseOrder::ESTADO_ANULACION_PENDIENTE, 'label' => 'Anulación pendiente'],
            ['value' => PurchaseOrder::ESTADO_ANULADA, 'label' => 'Anuladas'],
            ['value' => PurchaseOrder::ESTADO_BORRADOR, 'label' => 'Borrador'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function tipoOptions(): array
    {
        return [
            ['value' => self::TIPO_TODOS, 'label' => 'Todos'],
            ['value' => 'compra', 'label' => 'Solicitud de compra'],
            ['value' => 'suministro', 'label' => 'Solicitud de suministro'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function providerOptions(): array
    {
        return PurchaseProvider::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'identificacion'])
            ->map(fn (PurchaseProvider $provider): array => [
                'value' => (string) $provider->getKey(),
                'label' => $provider->nombre.' — '.$provider->identificacion,
            ])
            ->all();
    }

    private function rowActions(PurchaseOrder $order): string
    {
        $module = (string) request()->route('module');

        $show = sprintf(
            '<a href="%s" class="cursos-catalogo-page__icon-btn" title="Ver orden de compra" aria-label="Ver orden de compra">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>'.
            '</a>',
            e(route('purchase-requests.orders.show', ['module' => $module, 'purchase_order' => $order->getKey()])),
        );

        $pdf = sprintf(
            '<a href="%s" class="cursos-catalogo-page__icon-btn" title="Descargar PDF" aria-label="Descargar PDF" target="_blank" rel="noopener">'.
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '.
            'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.
            '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>'.
            '</a>',
            e(route('purchase-requests.orders.pdf', ['module' => $module, 'purchase_order' => $order->getKey()])),
        );

        return $show.$pdf;
    }
}
