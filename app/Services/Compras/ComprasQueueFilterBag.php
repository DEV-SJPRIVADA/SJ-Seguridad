<?php

namespace App\Services\Compras;

use App\Enums\PedidoEtapa;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ComprasQueueFilterBag
{
    public function __construct(
        public readonly string $estadoCompras,
        public readonly ?string $tipo,
        public readonly ?string $areaKey,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
    ) {}

    /**
     * @param  array{year: int, month: int|null, area_key: string, tipo: string}  $filters
     */
    public static function fromDashboardFilters(array $filters, string $estadoCompras = ''): self
    {
        [$dateFrom, $dateTo] = self::dateRangeFromDashboardFilters(
            (int) $filters['year'],
            $filters['month'] ?? null,
        );

        return new self(
            estadoCompras: $estadoCompras,
            tipo: ($filters['tipo'] ?? '') !== '' ? $filters['tipo'] : null,
            areaKey: ($filters['area_key'] ?? '') !== '' ? $filters['area_key'] : null,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );
    }

    /**
     * @param  array{year: int, month: int|null, area_key: string, tipo: string}  $filters
     * @return array<string, string>
     */
    public static function bandejaLinkQuery(array $filters, ?string $estadoCompras = null): array
    {
        $bag = self::fromDashboardFilters($filters, $estadoCompras ?? '');

        $query = collect($bag->toViewArray())
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): string => (string) $value)
            ->all();

        // Un solo escritorio: el dashboard abre la vista de Pedidos, no otro listado.
        if ($bag->vista() !== '') {
            $query['vista'] = $bag->vista();
            unset($query['estado_compras']);
        }

        return $query;
    }

    public static function estadoForVista(?string $vista): ?string
    {
        return match ($vista) {
            'por_comprar' => PurchaseRequest::COMPRAS_PENDIENTE,
            'con_orden' => PurchaseRequest::COMPRAS_EN_CURSO,
            'historial' => PurchaseRequest::COMPRAS_COMPLETADO,
            default => null,
        };
    }

    public function vista(): string
    {
        return match ($this->estadoCompras) {
            PurchaseRequest::COMPRAS_PENDIENTE => 'por_comprar',
            PurchaseRequest::COMPRAS_EN_CURSO => 'con_orden',
            PurchaseRequest::COMPRAS_COMPLETADO => 'historial',
            default => '',
        };
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    public static function dateRangeFromDashboardFilters(int $year, ?int $month): array
    {
        if ($month !== null && $month >= 1 && $month <= 12) {
            $start = Carbon::create($year, $month, 1);

            return [
                $start->copy()->startOfMonth()->toDateString(),
                $start->copy()->endOfMonth()->toDateString(),
            ];
        }

        if ($year > 0) {
            return ["{$year}-01-01", "{$year}-12-31"];
        }

        return [null, null];
    }

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'vista' => ['nullable', Rule::in(['por_comprar', 'con_orden', 'historial'])],
            'estado_compras' => ['nullable', Rule::in(array_keys(PurchaseRequest::estadosComprasLabels()))],
            'tipo' => ['nullable', 'in:purchase,supply'],
            'area_key' => ['nullable', Rule::in(array_keys(config('access.areas', [])))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $estadoDesdeVista = self::estadoForVista($validated['vista'] ?? null);

        return new self(
            estadoCompras: $estadoDesdeVista ?? (string) ($validated['estado_compras'] ?? ''),
            tipo: $validated['tipo'] ?? null,
            areaKey: $validated['area_key'] ?? null,
            dateFrom: self::normalizeDate($validated['date_from'] ?? null),
            dateTo: self::normalizeDate($validated['date_to'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        return [
            'vista' => $this->vista(),
            'estado_compras' => $this->estadoCompras,
            'tipo' => $this->tipo,
            'area_key' => $this->areaKey,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    public function hasActiveFilters(): bool
    {
        return $this->estadoCompras !== ''
            || $this->tipo !== null
            || $this->areaKey !== null
            || $this->hasDateRangeFilter();
    }

    public function hasDateRangeFilter(): bool
    {
        return $this->dateFrom !== null || $this->dateTo !== null;
    }

    /**
     * Etapas que entran en la vista actual. Sin vista: la bandeja (Por comprar + Con orden).
     * El filtro legado «rechazado» no muestra filas.
     *
     * @return list<PedidoEtapa>
     */
    public function etapas(): array
    {
        return match ($this->estadoCompras) {
            '' => [PedidoEtapa::PorComprar, PedidoEtapa::ConOrden],
            PurchaseRequest::COMPRAS_RECHAZADO => [],
            default => array_values(array_filter([PedidoEtapa::fromComprasKey($this->estadoCompras)])),
        };
    }

    /**
     * Estados de suministro que entran en la vista actual.
     *
     * @return list<string>
     */
    public function supplyQueueStatuses(): array
    {
        $statuses = [];

        foreach ($this->etapas() as $etapa) {
            array_push($statuses, ...$etapa->supplyStatuses());
        }

        return $statuses;
    }

    /**
     * @param  Builder<PurchaseRequest>  $query
     */
    public function applyPurchaseFilters(Builder $query): void
    {
        if ($this->areaKey) {
            $query->where('area_key', $this->areaKey);
        }

        $query->whereEtapa($this->etapas());

        if ($this->dateFrom !== null) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== null) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
    }

    /**
     * @param  Builder<SupplyRequest>  $query
     */
    public function applySupplyFilters(Builder $query): void
    {
        if ($this->areaKey) {
            $query->where('area_key', $this->areaKey);
        }

        if ($this->dateFrom !== null) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== null) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
