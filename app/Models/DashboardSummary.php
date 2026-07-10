<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardSummary extends Model
{
    protected $table = 'indicator_dashboard_summaries';

    protected $fillable = [
        'year',
        'month',
        'summary_text',
        'generated_by_user_id',
        'updated_by_user_id',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
