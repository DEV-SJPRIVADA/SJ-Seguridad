<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndicatorSystemDocument extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'scope',
        'indicator_id',
        'current_version_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(IndicatorSystemDocumentVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(IndicatorSystemDocumentVersion::class, 'current_version_id');
    }
}
