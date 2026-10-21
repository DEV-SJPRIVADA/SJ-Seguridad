<?php

namespace App\Services\PurchaseRequests;

use App\Enums\PedidoEtapa;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Filtros GET de Mis solicitudes. No cambia el alcance: solo las del usuario autenticado.
 */
final class PurchaseMyRequestsFilterBag
{
    public function __construct(
        public readonly ?PedidoEtapa $etapa,
        public readonly ?string $areaKey,
        public readonly ?bool $urgente,
        public readonly ?string $dateFrom,
        public readonly ?string $dateTo,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = $request->validate([
            'etapa' => ['nullable', Rule::enum(PedidoEtapa::class)],
            'area_key' => ['nullable', Rule::in(array_keys(config('access.areas', [])))],
            'urgente' => ['nullable', 'in:0,1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $etapa = self::nullableString($validated['etapa'] ?? null);
        $urgente = self::nullableString($validated['urgente'] ?? null);

        return new self(
            etapa: $etapa !== null ? PedidoEtapa::from($etapa) : null,
            areaKey: self::nullableString($validated['area_key'] ?? null),
            urgente: $urgente === null ? null : $urgente === '1',
            dateFrom: self::nullableString($validated['date_from'] ?? null),
            dateTo: self::nullableString($validated['date_to'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        return [
            'etapa' => $this->etapa?->value,
            'area_key' => $this->areaKey,
            'urgente' => $this->urgente === null ? '' : ($this->urgente ? '1' : '0'),
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    public function hasActiveFilters(): bool
    {
        return $this->etapa !== null
            || $this->areaKey !== null
            || $this->urgente !== null
            || $this->dateFrom !== null
            || $this->dateTo !== null;
    }

    /**
     * @param  Builder<PurchaseRequest>  $query
     */
    public function apply(Builder $query): void
    {
        if ($this->etapa !== null) {
            $query->whereEtapa($this->etapa);
        }

        if ($this->areaKey !== null) {
            $query->where('area_key', $this->areaKey);
        }

        if ($this->urgente !== null) {
            $query->where('urgente', $this->urgente);
        }

        if ($this->dateFrom !== null) {
            $query->whereDate('fecha_solicitud', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== null) {
            $query->whereDate('fecha_solicitud', '<=', $this->dateTo);
        }
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
