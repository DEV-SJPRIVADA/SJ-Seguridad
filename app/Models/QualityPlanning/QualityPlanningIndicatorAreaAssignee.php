<?php

namespace App\Models\QualityPlanning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityPlanningIndicatorAreaAssignee extends Model
{
    protected $table = 'quality_planning_indicator_area_assignees';

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
        'area_key',
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
}
