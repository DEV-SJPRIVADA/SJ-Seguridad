<?php

namespace App\Models\QualityPlanning;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityPlanningDofaItem extends Model
{
    public const QUADRANTS = [
        'fortaleza' => 'Fortalezas',
        'oportunidad' => 'Oportunidades',
        'debilidad' => 'Debilidades',
        'amenaza' => 'Amenazas',
    ];

    protected $table = 'quality_planning_dofa_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dofa_id',
        'quadrant',
        'description',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function dofa(): BelongsTo
    {
        return $this->belongsTo(QualityPlanningDofa::class, 'dofa_id');
    }

    public function quadrantLabel(): string
    {
        return self::QUADRANTS[$this->quadrant] ?? $this->quadrant;
    }
}
