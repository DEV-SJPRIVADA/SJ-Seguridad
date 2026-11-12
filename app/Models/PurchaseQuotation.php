<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Cotización cargada por Compras para una solicitud de compra.
 *
 * Las cotizaciones se agrupan por `lote`: cada lote es una OC futura y puede llevar
 * hasta 3 cotizaciones. Compras envía el lote al director, que elige una ganadora.
 * Flujo por cotización: Cargada → (Compras solicita) Pendiente → Aprobada | No seleccionada | Rechazada.
 * La aprobada puede vincularse a una sola OC (o a ninguna: es opcional).
 */
class PurchaseQuotation extends Model
{
    public const ESTADO_CARGADA = 'cargada';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADA = 'aprobada';

    public const ESTADO_NO_SELECCIONADA = 'no_seleccionada';

    public const ESTADO_RECHAZADA = 'rechazada';

    protected $fillable = [
        'purchase_request_id',
        'lote',
        'lote_label',
        'lote_orden',
        'uploaded_by',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
        'description',
        'estado',
        'aprobador_id',
        'solicitada_por',
        'solicitada_at',
        'comentario',
        'resuelta_at',
        'seleccionada',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'lote_orden' => 'integer',
            'solicitada_at' => 'datetime',
            'resuelta_at' => 'datetime',
            'seleccionada' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (PurchaseQuotation $quotation): void {
            $disk = (string) config('purchase-requests.quotations.disk', 'local');

            if ($quotation->stored_path !== '' && Storage::disk($disk)->exists($quotation->stored_path)) {
                Storage::disk($disk)->delete($quotation->stored_path);
            }
        });
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobador_id');
    }

    public function solicitadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitada_por');
    }

    /**
     * OC que consumió esta cotización. Una cotización no puede usarse en dos OC.
     */
    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'purchase_quotation_id');
    }

    /**
     * @param  Builder<PurchaseQuotation>  $query
     * @return Builder<PurchaseQuotation>
     */
    public function scopeAprobadasDisponibles(Builder $query): Builder
    {
        return $query
            ->where('estado', self::ESTADO_APROBADA)
            ->where('seleccionada', true)
            ->whereDoesntHave('purchaseOrder');
    }

    /**
     * @param  Builder<PurchaseQuotation>  $query
     * @return Builder<PurchaseQuotation>
     */
    public function scopeDelLote(Builder $query, string $lote): Builder
    {
        return $query->where('lote', $lote);
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function estaAprobada(): bool
    {
        return $this->estado === self::ESTADO_APROBADA;
    }

    public function estaNoSeleccionada(): bool
    {
        return $this->estado === self::ESTADO_NO_SELECCIONADA;
    }

    public function estaRechazada(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADA;
    }

    public function puedeParticiparEnLote(): bool
    {
        return in_array($this->estado, [self::ESTADO_CARGADA, self::ESTADO_RECHAZADA], true);
    }

    public function estaVinculada(): bool
    {
        return $this->purchaseOrder()->exists();
    }

    public function puedeSolicitarAprobacion(): bool
    {
        return ! $this->estaVinculada()
            && in_array($this->estado, [self::ESTADO_CARGADA, self::ESTADO_RECHAZADA], true);
    }

    public function puedeRetirarSolicitud(): bool
    {
        return $this->estaPendiente();
    }

    public function puedeEliminar(): bool
    {
        return ! $this->estaVinculada()
            && in_array($this->estado, [self::ESTADO_CARGADA, self::ESTADO_RECHAZADA], true);
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'Aprobación pendiente',
            self::ESTADO_APROBADA => 'Aprobada (seleccionada)',
            self::ESTADO_NO_SELECCIONADA => 'No seleccionada',
            self::ESTADO_RECHAZADA => 'Rechazada',
            default => 'Cargada',
        };
    }

    public function estadoPillClass(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'status-pill--warning',
            self::ESTADO_APROBADA => 'status-pill--success',
            self::ESTADO_NO_SELECCIONADA => 'status-pill--muted',
            self::ESTADO_RECHAZADA => 'status-pill--danger',
            default => 'status-pill--muted',
        };
    }

    public function descripcionVisible(): string
    {
        $description = trim((string) $this->description);

        return $description !== '' ? $description : $this->original_name;
    }

    public function loteLabel(): string
    {
        $label = trim((string) $this->lote_label);

        return $label !== '' ? $label : 'LOTE SIN REFERENCIA';
    }

    public function sizeLabel(): string
    {
        return Number::fileSize($this->size_bytes);
    }

    public function esPdf(): bool
    {
        return Str::contains(Str::lower((string) $this->mime_type), 'pdf')
            || Str::endsWith(Str::lower($this->original_name), '.pdf');
    }

    public function esImagen(): bool
    {
        return Str::startsWith(Str::lower((string) $this->mime_type), 'image/')
            || Str::endsWith(Str::lower($this->original_name), ['.jpg', '.jpeg', '.png']);
    }
}
