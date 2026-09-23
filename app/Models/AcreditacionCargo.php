<?php

namespace App\Models;

use Database\Factories\AcreditacionCargoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcreditacionCargo extends Model
{
    /** @use HasFactory<AcreditacionCargoFactory> */
    use HasFactory;

    protected $table = 'acreditacion_cargos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'cargo_manager',
        'cargo_apo',
        'cargo_informe',
        'cargo_acreditacion',
        'is_active',
        'sort_order',
    ];

    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('cargo_manager')->orderBy('id');
    }

    public function scopeForCargoApo(Builder $query, string $cargoApo): Builder
    {
        return $query->whereRaw('LOWER(TRIM(cargo_apo)) = ?', [mb_strtolower(trim($cargoApo))]);
    }
}
