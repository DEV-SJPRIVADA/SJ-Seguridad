<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplySite extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'utilization',
        'city',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('city')->orderBy('utilization');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'sede_id');
    }

    public function supplyRequests(): HasMany
    {
        return $this->hasMany(SupplyRequest::class, 'sede_id');
    }
}
