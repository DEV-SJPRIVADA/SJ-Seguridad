<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PayrollCatalogItem extends Model
{
    protected $fillable = [
        'catalog_type',
        'code',
        'name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('catalog_type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function upsertPair(string $type, ?string $code, ?string $name): ?self
    {
        $code = trim((string) $code);
        $name = trim((string) $name);

        if ($code === '' && $name === '') {
            return null;
        }

        if ($code === '') {
            $code = mb_substr(preg_replace('/\s+/', '_', mb_strtoupper($name)) ?? $name, 0, 50);
        }

        return self::query()->updateOrCreate(
            ['catalog_type' => $type, 'code' => $code],
            ['name' => $name !== '' ? $name : $code, 'is_active' => true]
        );
    }
}
