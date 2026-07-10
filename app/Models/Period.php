<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'indicator_periods';

    protected $fillable = [
        'year',
        'month',
        'status',
        'closed_at',
        'closed_by_user_id',
        'reopened_at',
        'reopened_by_user_id',
        'reopen_reason',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_user_id');
    }

    public function captures(): HasMany
    {
        return $this->hasMany(IndicatorCapture::class);
    }

    public function improvements(): HasMany
    {
        return $this->hasMany(Improvement::class);
    }
}
