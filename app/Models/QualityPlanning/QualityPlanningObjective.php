<?php

namespace App\Models\QualityPlanning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityPlanningObjective extends Model
{
    protected $table = 'quality_planning_objectives';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dofa_id',
        'title',
        'description',
        'starts_on',
        'ends_on',
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
            'sort' => 'integer',
        ];
    }

    public function dofa(): BelongsTo
    {
        return $this->belongsTo(QualityPlanningDofa::class, 'dofa_id');
    }

    public function strategies(): HasMany
    {
        return $this->hasMany(QualityPlanningStrategy::class, 'objective_id')->orderBy('sort');
    }
}
