<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Tasa de IVA del catálogo de Compras (0%, 5%, 19%…). Se elige por línea al emitir una OC.
 *
 * @property int $id
 * @property string $nombre
 * @property string $porcentaje
 * @property bool $activo
 */
class PurchaseTaxRate extends Model
{
    public const DEFAULT_PORCENTAJE = 19.0;

    protected $fillable = ['nombre', 'porcentaje', 'activo'];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo' => 'boolean',
    ];

    /**
     * @param  Builder<PurchaseTaxRate>  $query
     * @return Builder<PurchaseTaxRate>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('porcentaje');
    }

    /**
     * Texto corto del porcentaje: 19%, 5%, 0%, 2.5%.
     */
    public static function formatPorcentaje(float|string|null $porcentaje): string
    {
        if ($porcentaje === null || $porcentaje === '') {
            return '';
        }

        return rtrim(rtrim(number_format((float) $porcentaje, 2, '.', ''), '0'), '.').'%';
    }

    public function label(): string
    {
        return self::formatPorcentaje($this->porcentaje).' — '.$this->nombre;
    }
}
