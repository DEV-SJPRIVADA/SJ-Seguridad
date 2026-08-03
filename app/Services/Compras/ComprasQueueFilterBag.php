<?php

namespace App\Services\Compras;

use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'estado_compras' => ['nullable', Rule::in(array_keys(PurchaseRequest::estadosComprasLabels()))],
            'tipo' => ['nullable', 'in:purchase,supply'],
            'area_key' => ['nullable', Rule::in(array_keys(config('access.areas', [])))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        return new self(
            estadoCompras: (string) ($validated['estado_compras'] ?? ''),
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
            || $this->dateFrom !== null
            || $this->dateTo !== null;
    }

    /**
     * @param  Builder<PurchaseRequest>  $query
     */
    public function applyPurchaseFilters(Builder $query): void
    {
        if ($this->areaKey) {
            $query->where('area_key', $this->areaKey);
        }

        if ($this->estadoCompras !== '') {
            $query->where('estado_compras', $this->estadoCompras);
        }

        if ($this->dateFrom !== null) {
            $query->whereDate(DB::raw('COALESCE(fecha_aprobacion, created_at)'), '>=', $this->dateFrom);
        }

        if ($this->dateTo !== null) {
            $query->whereDate(DB::raw('COALESCE(fecha_aprobacion, created_at)'), '<=', $this->dateTo);
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

        if ($this->estadoCompras === PurchaseRequest::COMPRAS_PENDIENTE) {
            $query->where('status', 'aprobada_calidad');
        } elseif ($this->estadoCompras === PurchaseRequest::COMPRAS_EN_CURSO) {
            $query->where('status', 'en_compras');
        } elseif (in_array($this->estadoCompras, [PurchaseRequest::COMPRAS_COMPLETADO, PurchaseRequest::COMPRAS_RECHAZADO], true)) {
            $query->whereRaw('1 = 0');
        }

        if ($this->dateFrom !== null) {
            $query->whereDate('updated_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== null) {
            $query->whereDate('updated_at', '<=', $this->dateTo);
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
