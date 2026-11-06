<?php

namespace App\Models;

use App\Models\Concerns\ServicioClienteCatalog;
use Illuminate\Database\Eloquent\Model;

class ServicioClienteTipologia extends Model
{
    use ServicioClienteCatalog;

    protected $fillable = ['code', 'name', 'is_active', 'sort_order'];
}
