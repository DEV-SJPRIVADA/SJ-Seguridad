<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialPortfolio extends Model
{
    protected $fillable = ['slug', 'name', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
