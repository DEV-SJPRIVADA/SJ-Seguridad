<?php

namespace App\Models\QualityPlanning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityPlanningDofa extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'quality_planning_dofas';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'period_label',
        'starts_on',
        'ends_on',
        'status',
        'summary',
        'created_by_user_id',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QualityPlanningDofaItem::class, 'dofa_id')->orderBy('sort');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(QualityPlanningObjective::class, 'dofa_id')->orderBy('sort');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null || $this->status === self::STATUS_ARCHIVED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Vigente',
            self::STATUS_ARCHIVED => 'Archivado',
            default => 'Borrador',
        };
    }

    /**
     * @return array<string, list<QualityPlanningDofaItem>>
     */
    public function itemsByQuadrant(): array
    {
        $grouped = ['fortaleza' => [], 'oportunidad' => [], 'debilidad' => [], 'amenaza' => []];

        foreach ($this->items as $item) {
            if (! array_key_exists($item->quadrant, $grouped)) {
                continue;
            }

            $grouped[$item->quadrant][] = $item;
        }

        return $grouped;
    }
}
