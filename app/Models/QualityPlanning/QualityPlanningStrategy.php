<?php

namespace App\Models\QualityPlanning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityPlanningStrategy extends Model
{
    protected $table = 'quality_planning_strategies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'objective_id',
        'title',
        'description',
        'responsible',
        'starts_on',
        'ends_on',
        'progress',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'progress' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function objective(): BelongsTo
    {
        return $this->belongsTo(QualityPlanningObjective::class, 'objective_id');
    }
}
