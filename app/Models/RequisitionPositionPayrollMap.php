<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionPositionPayrollMap extends Model
{
    protected $fillable = [
        'requisition_position_id',
        'payroll_position_code',
    ];

    public function requisitionPosition(): BelongsTo
    {
        return $this->belongsTo(RequisitionPosition::class);
    }
}
