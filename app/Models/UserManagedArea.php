<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserManagedArea extends Model
{
    protected $fillable = [
        'user_id',
        'area_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function areaLabel(): ?string
    {
        return config("access.areas.{$this->area_key}");
    }
}
