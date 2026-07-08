<?php

namespace App\Models;

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

    public function site(): BelongsTo
    {
        return $this->belongsTo(SupplySite::class, 'sede_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pendiente_calidad' => 'Pendiente',
            'aprobada_calidad' => 'Aprobada',
            'rechazada_calidad' => 'Rechazada',
            'en_compras' => 'En compras',
            'completada' => 'Completada',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
