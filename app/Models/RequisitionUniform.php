<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisitionUniform extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active', 'sort_order'];
}
