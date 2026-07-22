<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalRequisitionChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'personal_requisition_id',
        'change_batch',
        'field_key',
        'field_label',
        'old_value',
        'new_value',
        'changed_by',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PersonalRequisition::class, 'personal_requisition_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
