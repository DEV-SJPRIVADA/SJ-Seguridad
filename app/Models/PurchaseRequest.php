<?php

namespace App\Models;

use App\Enums\PedidoEtapa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseRequest extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_RECHAZADO = 'rechazado';

    public const COMPRAS_PENDIENTE = 'pendiente';

    public const COMPRAS_EN_CURSO = 'en_curso';

    public const COMPRAS_COMPLETADO = 'completado';

    public const COMPRAS_RECHAZADO = 'rechazado';

    protected $fillable = [
        'numero_solicitud',
        'user_id',
        'area_key',
        'fecha_solicitud',
        'cantidad',
        'numero_cotizacion',
        'comentarios_solicitante',
        'solicitud_para',
        'urgente',
        'aprobador_id',
        'proyecto_nuevo',
        'razon_social',
        'asume_cliente',
        'reinversion',
        'comodato',
        'activo',
        'estado',
        'estado_compras',
        'fecha_aprobacion',
        'comentarios_director',
        'procesado_compras_at',
        'procesado_compras_por',
        'comentarios_compras',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date',
            'fecha_aprobacion' => 'date',
            'procesado_compras_at' => 'datetime',
            'urgente' => 'boolean',
            'proyecto_nuevo' => 'boolean',
            'asume_cliente' => 'boolean',
            'reinversion' => 'boolean',
            'comodato' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Etiquetas de `estado_compras` con el nombre de la etapa común.
     *
     * @return array<string, string>
     */
    public static function estadosComprasLabels(): array
    {
        $labels = [];

        foreach ([self::COMPRAS_PENDIENTE, self::COMPRAS_EN_CURSO, self::COMPRAS_COMPLETADO, self::COMPRAS_RECHAZADO] as $key) {
            $labels[$key] = PedidoEtapa::fromComprasKey($key)->label();
        }

        return $labels;
    }

    public function etapa(): PedidoEtapa
    {
        return PedidoEtapa::fromPurchase($this->estado, $this->estado_compras);
    }

    /**
     * Filtra por una o varias etapas traduciendo a `estado` + `estado_compras`.
     *
     * @param  Builder<PurchaseRequest>  $query
     * @param  PedidoEtapa|list<PedidoEtapa>  $etapas
     * @return Builder<PurchaseRequest>
     */
    public function scopeWhereEtapa(Builder $query, PedidoEtapa|array $etapas): Builder
    {
        $etapas = is_array($etapas) ? $etapas : [$etapas];

        if ($etapas === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($etapas): void {
            foreach ($etapas as $etapa) {
                $outer->orWhere(fn (Builder $sub): Builder => match ($etapa) {
                    PedidoEtapa::PorAutorizar => $sub->where('estado', self::ESTADO_PENDIENTE),
                    PedidoEtapa::Devuelta => $sub->where('estado', self::ESTADO_RECHAZADO),
                    default => $sub->where('estado', self::ESTADO_APROBADO)
                        ->where('estado_compras', $etapa->comprasKey()),
                });
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobador_id');
    }

    public function procesadoComprasPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procesado_compras_por');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class)->orderBy('orden');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseRequestAttachment::class)->orderBy('sort_order')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PurchaseRequestComment::class)->oldest('id');
    }

    /**
     * Órdenes de compra emitidas desde esta solicitud (incluye anuladas).
     */
    public function purchaseOrders(): MorphMany
    {
        return $this->morphMany(PurchaseOrder::class, 'source')->orderBy('id');
    }

    public function mailLogs(): HasMany
    {
        return $this->hasMany(PurchaseRequestMailLog::class)->latest('sent_at')->latest('id');
    }

    /**
     * Marcas distintas de los productos (para listados). Usa los ítems ya cargados si los hay.
     */
    public function marcasResumen(): string
    {
        return $this->items
            ->pluck('marca')
            ->map(fn ($marca): string => trim((string) $marca))
            ->filter()
            ->unique()
            ->implode(', ');
    }

    public function areaLabel(): ?string
    {
        return config("access.areas.{$this->area_key}");
    }

    public function folio(): string
    {
        return str_pad((string) $this->numero_solicitud, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Folio de pantalla. No altera el correlativo guardado; por encima de 9999 no recorta.
     */
    public function folioVisible(): string
    {
        return 'SC-'.$this->folio();
    }

    public function estaEnBandejaCompras(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }

    /** @return array<string, string> */
    public static function estadosLabels(): array
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_APROBADO => 'Aprobado',
            self::ESTADO_RECHAZADO => 'Rechazado',
        ];
    }

    public function estadoLabel(): string
    {
        return self::estadosLabels()[$this->estado] ?? ucfirst((string) $this->estado);
    }

    public function estadoComprasLabel(): string
    {
        return self::estadosComprasLabels()[$this->estado_compras] ?? '—';
    }

    /**
     * Etiqueta de la máquina (Por comprar / Con orden / Cerrado). El valor en BD no cambia.
     */
    public static function etiquetaFlujo(?string $estadoCompras): string
    {
        return PedidoEtapa::fromComprasKey($estadoCompras)?->label() ?? '—';
    }

    public function etiquetaFlujoCompras(): string
    {
        return self::etiquetaFlujo($this->estado_compras);
    }

    public function puedeCerrarSaldo(): bool
    {
        return $this->estado === self::ESTADO_APROBADO
            && in_array($this->estado_compras, [self::COMPRAS_PENDIENTE, self::COMPRAS_EN_CURSO], true);
    }

    public function puedeEmitirOrden(): bool
    {
        return $this->puedeCerrarSaldo();
    }

    /**
     * Compras puede devolverla al solicitante solo en Por comprar y sin ninguna orden emitida.
     */
    public function puedeDevolverAlSolicitante(): bool
    {
        return $this->estado === self::ESTADO_APROBADO
            && $this->estado_compras === self::COMPRAS_PENDIENTE
            && ! PurchaseOrder::query()
                ->where('source_type', 'purchase_request')
                ->where('source_id', $this->getKey())
                ->where('estado', '!=', PurchaseOrder::ESTADO_ANULADA)
                ->exists();
    }

    public function puedeReabrir(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADO;
    }

    public function puedeComentar(): bool
    {
        return $this->estado_compras !== self::COMPRAS_COMPLETADO;
    }
}
