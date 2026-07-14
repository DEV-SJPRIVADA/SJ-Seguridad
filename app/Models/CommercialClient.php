<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommercialClient extends Model
{
    protected $fillable = [
        'nit',
        'name',
        'phone',
        'address',
        'city',
        'legal_rep_name',
        'legal_rep_doc',
        'created_by',
        'updated_by',
    ];

    public static function normalizeNit(?string $nit): string
    {
        $nit = trim((string) $nit);
        $nit = str_replace(['.', ' ', ','], '', $nit);

        return $nit;
    }

    public function setNitAttribute(?string $value): void
    {
        $this->attributes['nit'] = self::normalizeNit($value);
    }

    public function services(): HasMany
    {
        return $this->hasMany(CommercialService::class);
    }

    public function activeServices(): HasMany
    {
        return $this->services()->where('portfolio', '!=', CommercialService::PORTFOLIO_INACTIVOS);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
