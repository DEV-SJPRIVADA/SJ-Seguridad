<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommercialSector extends Model
{
    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function services(): HasMany
    {
        return $this->hasMany(CommercialService::class, 'commercial_sector_id');
    }
}
