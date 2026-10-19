<?php

namespace App\Models;

use Database\Factories\PurchaseProviderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseProvider extends Model
{
    /** @use HasFactory<PurchaseProviderFactory> */
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_INACTIVO = 'inactivo';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'identificacion',
        'nombre',
        'telefono',
        'correo',
        'es_cliente',
        'es_proveedor',
        'estado',
        'dias_pago',
        'contacto',
        'direccion',
        'ciudad',
        'forma_pago',
        'created_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'es_cliente' => false,
        'es_proveedor' => true,
        'estado' => self::ESTADO_ACTIVO,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_cliente' => 'boolean',
            'es_proveedor' => 'boolean',
            'dias_pago' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    /**
     * @return array<string, string>
     */
    public static function estadoLabels(): array
    {
        return [
            self::ESTADO_ACTIVO => 'Activo',
            self::ESTADO_INACTIVO => 'Inactivo',
        ];
    }
}
