<?php

namespace App\Models\QualityPlanning;

use App\Models\User;
use Database\Factories\QualityPlanning\QualityPlanningIndicatorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityPlanningIndicator extends Model
{
    /** @use HasFactory<QualityPlanningIndicatorFactory> */
    use HasFactory;

    protected $table = 'quality_planning_indicators';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'created_by_user_id',
        'archived_at',
        'current_published_version_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'current_published_version_id' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function currentPublishedVersion(): BelongsTo
    {
        return $this->belongsTo(QualityPlanningIndicatorVersion::class, 'current_published_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(QualityPlanningIndicatorVersion::class, 'indicator_id');
    }

    public function assignees(): HasMany
    {
        return $this->hasMany(QualityPlanningIndicatorAssignee::class, 'indicator_id');
    }

    public function areaAssignees(): HasMany
    {
        return $this->hasMany(QualityPlanningIndicatorAreaAssignee::class, 'indicator_id');
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(QualityPlanningIndicatorSubstitution::class, 'indicator_id');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function draftVersion(): ?QualityPlanningIndicatorVersion
    {
        return $this->versions
            ->where('status', QualityPlanningIndicatorVersion::STATUS_DRAFT)
            ->sortByDesc('version_number')
            ->first();
    }

    public function workingVersion(): ?QualityPlanningIndicatorVersion
    {
        return $this->draftVersion()
            ?? $this->currentPublishedVersion
            ?? $this->versions->sortByDesc('version_number')->first();
    }
}
