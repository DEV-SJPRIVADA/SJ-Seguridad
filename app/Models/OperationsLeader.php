<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationsLeader extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
