<?php

namespace App\Models;

use App\Enums\PedidoEtapa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'area_key',
        'sede_id',
        'site_utilization',
        'site_city',
        'status',
        'observations',
        'quality_reviewer_id',
        'quality_observations',
        'purchasing_manager_id',
        'total_cost',
        'exported_at',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'exported_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function qualityReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_reviewer_id');
    }

    public function purchasingManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchasing_manager_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplyRequestItem::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SupplyRequestComment::class)->oldest('id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SupplySite::class, 'sede_id');
    }

    /**
     * Etiqueta de pantalla: la etapa común (Por autorizar, Por comprar, Con orden…).
     */
    public function statusLabel(): string
    {
        return $this->etapa()->label();
    }

    public function etapa(): PedidoEtapa
    {
        return PedidoEtapa::fromSupply($this->status);
    }

    /**
     * Filtra por una o varias etapas traduciendo a `status`.
     *
     * @param  Builder<SupplyRequest>  $query
     * @param  PedidoEtapa|list<PedidoEtapa>  $etapas
     * @return Builder<SupplyRequest>
     */
    public function scopeWhereEtapa(Builder $query, PedidoEtapa|array $etapas): Builder
    {
        $statuses = [];

        foreach (is_array($etapas) ? $etapas : [$etapas] as $etapa) {
            array_push($statuses, ...$etapa->supplyStatuses());
        }

        $statuses = array_values(array_unique($statuses));

        return $statuses === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('status', $statuses);
    }

    public function folio(): string
    {
        return str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Folio de pantalla. No altera el id guardado; por encima de 9999 no recorta.
     */
    public function folioVisible(): string
    {
        return 'SU-'.$this->folio();
    }

    public function estadoComprasKey(): ?string
    {
        return $this->etapa()->comprasKey();
    }

    public function estadoComprasLabel(): ?string
    {
        $key = $this->estadoComprasKey();

        return $key !== null
            ? (PurchaseRequest::estadosComprasLabels()[$key] ?? null)
            : null;
    }

    public function isExportableForCompras(): bool
    {
        return in_array($this->status, ['aprobada_calidad', 'en_compras', 'completada'], true);
    }

    public function puedeCerrarSaldo(): bool
    {
        return in_array($this->status, ['aprobada_calidad', 'en_compras'], true);
    }

    public function puedeEmitirOrden(): bool
    {
        return $this->puedeCerrarSaldo();
    }

    /**
     * Compras puede devolverlo al solicitante solo en Por comprar y sin ninguna orden emitida.
     */
    public function puedeDevolverAlSolicitante(): bool
    {
        return $this->status === 'aprobada_calidad'
            && ! PurchaseOrder::query()
                ->where('source_type', 'supply_request')
                ->where('source_id', $this->getKey())
                ->where('estado', '!=', PurchaseOrder::ESTADO_ANULADA)
                ->exists();
    }

    public function puedeComentar(): bool
    {
        return $this->status !== 'completada';
    }

    public function etiquetaFlujoCompras(): ?string
    {
        $key = $this->estadoComprasKey();

        return $key !== null ? PurchaseRequest::etiquetaFlujo($key) : null;
    }
}
