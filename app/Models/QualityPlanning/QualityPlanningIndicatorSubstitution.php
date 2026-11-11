<?php

namespace App\Models\QualityPlanning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Suplencia temporal: el suplente captura a nombre del titular dentro del rango.
 */
class QualityPlanningIndicatorSubstitution extends Model
{
    protected $table = 'quality_planning_indicator_substitutions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'indicator_id',
        'holder_user_id',
        'substitute_user_id',
        'starts_on',
        'ends_on',
        'created_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(QualityPlanningIndicator::class, 'indicator_id');
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }

    public function substitute(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_user_id');
    }
}
