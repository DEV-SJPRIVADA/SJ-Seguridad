<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Indicator extends Model
{
    protected $fillable = [
        'code',
        'name',
        'unit',
        'target_value',
        'critical_value',
        'target_operator',
        'frequency',
        'formula_description',
        'required_fields',
        'allows_over_100',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'critical_value' => 'decimal:2',
            'required_fields' => 'array',
            'allows_over_100' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function dashboardWeight(): HasOne
    {
        return $this->hasOne(DashboardWeight::class);
    }

    public function captures(): HasMany
    {
        return $this->hasMany(IndicatorCapture::class);
    }
}
