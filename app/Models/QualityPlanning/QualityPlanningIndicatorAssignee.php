<?php

namespace App\Models\QualityPlanning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityPlanningIndicatorAssignee extends Model
{
    protected $table = 'quality_planning_indicator_assignees';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'can_capture' => true,
        'can_consult' => false,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'indicator_id',
        'user_id',
        'can_capture',
        'can_consult',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_capture' => 'boolean',
            'can_consult' => 'boolean',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(QualityPlanningIndicator::class, 'indicator_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
