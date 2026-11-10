<?php

namespace App\Models\QualityPlanning;

use App\Models\User;
use Database\Factories\QualityPlanning\QualityPlanningIndicatorVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityPlanningIndicatorVersion extends Model
{
    /** @use HasFactory<QualityPlanningIndicatorVersionFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SUPERSEDED = 'superseded';

    public const UNIT_PERCENTAGE = 'percentage';

    public const FREQUENCY_MONTHLY = 'monthly';

    protected $table = 'quality_planning_indicator_versions';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'unit' => self::UNIT_PERCENTAGE,
        'frequency' => self::FREQUENCY_MONTHLY,
        'target_operator' => '>=',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'indicator_id',
        'version_number',
        'status',
        'name',
        'objective',
        'insumos',
        'medicion',
        'process_key',
        'unit',
        'frequency',
        'target_value',
        'target_operator',
        'critical_value',
        'formula',
        'fields',
        'published_at',
        'published_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'target_value' => 'decimal:2',
            'critical_value' => 'decimal:2',
            'fields' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(QualityPlanningIndicator::class, 'indicator_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function captures(): HasMany
    {
        return $this->hasMany(QualityPlanningCapture::class, 'indicator_version_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isFrozen(): bool
    {
        return $this->status !== self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_PUBLISHED => 'Publicado',
            self::STATUS_SUPERSEDED => 'Reemplazado',
            default => $this->status,
        };
    }

    public function unitLabel(): string
    {
        return match ($this->unit) {
            self::UNIT_PERCENTAGE => 'Porcentaje',
            'number' => 'Número',
            default => (string) $this->unit,
        };
    }

    public function frequencyLabel(): string
    {
        return match ($this->frequency) {
            self::FREQUENCY_MONTHLY => 'Mensual',
            default => (string) $this->frequency,
        };
    }

    public function processLabel(): string
    {
        if ($this->process_key === null || $this->process_key === '') {
            return '—';
        }

        return (string) config('quality-documents.processes.'.$this->process_key, $this->process_key);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function fieldLabelPairs(): array
    {
        $pairs = [];

        foreach ($this->fields ?? [] as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = (string) ($field['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $pairs[] = [
                'key' => $key,
                'label' => (string) ($field['label'] ?? $key),
            ];
        }

        return $pairs;
    }
}
