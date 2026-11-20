<?php

namespace App\Models\Calidad;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo simple compartido por los cinco catálogos de MT-GI-11.
 *
 * Todos tienen code/name/is_active/sort_order; los estados agregan `color`
 * con la variante de `status-pill` (warning/info/success/danger/muted).
 */
abstract class MtGi11Catalog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['code', 'name', 'is_active', 'sort_order'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
