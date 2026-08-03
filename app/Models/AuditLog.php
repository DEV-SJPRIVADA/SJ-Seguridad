<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'module',
        'area',
        'user_id',
        'event_type',
        'action',
        'auditable_type',
        'auditable_id',
        'change_batch',
        'old_values',
        'new_values',
        'metadata',
        'reason',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    public function scopeForArea(Builder $query, string $area): Builder
    {
        return $query->where('area', $area);
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    public function scopeBetweenDates(Builder $query, ?Carbon $from, ?Carbon $to): Builder
    {
        return $query
            ->when($from, fn (Builder $builder) => $builder->where('created_at', '>=', $from))
            ->when($to, fn (Builder $builder) => $builder->where('created_at', '<=', $to));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
