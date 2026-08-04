<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagementReportDraft extends Model
{
    protected $table = 'indicator_management_report_drafts';

    protected $fillable = [
        'year',
        'month',
        'report_title',
        'narratives',
        'updated_by_user_id',
    ];

    protected $casts = [
        'narratives' => 'array',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
