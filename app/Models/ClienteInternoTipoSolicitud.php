<?php

namespace App\Models;

use Database\Factories\ClienteInternoTipoSolicitudFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteInternoTipoSolicitud extends Model
{
    /** @use HasFactory<ClienteInternoTipoSolicitudFactory> */
    use HasFactory;

    protected $table = 'cliente_interno_tipos_solicitud';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

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

    public function solicitudes(): HasMany
    {
        return $this->hasMany(ClienteInternoSolicitud::class, 'tipo_solicitud_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
