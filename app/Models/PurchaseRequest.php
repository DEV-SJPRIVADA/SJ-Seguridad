<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'descripcion',
        'cantidad',
        'justificacion',
        'archivo_pedido_path',
        'solicitud_para',
        'urgente',
        'aprobador_id',
        'proyecto_nuevo',
        'razon_social',
        'asume_cliente',
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
        ];
    }

    /** @return array<string, string> */
    public static function estadosComprasLabels(): array
    {
        return [
            self::COMPRAS_PENDIENTE => 'Pendiente',
            self::COMPRAS_EN_CURSO => 'En curso',
            self::COMPRAS_COMPLETADO => 'Completado',
            self::COMPRAS_RECHAZADO => 'Rechazado',
        ];
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

    public function mailLogs(): HasMany
    {
        return $this->hasMany(PurchaseRequestMailLog::class)->latest('sent_at')->latest('id');
    }

    public function areaLabel(): ?string
    {
        return config("access.areas.{$this->area_key}");
    }

    public function folio(): string
    {
        return str_pad((string) $this->numero_solicitud, 4, '0', STR_PAD_LEFT);
    }

    public function estaEnBandejaCompras(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }

    public function estadoComprasLabel(): string
    {
        return self::estadosComprasLabels()[$this->estado_compras] ?? '—';
    }
}
