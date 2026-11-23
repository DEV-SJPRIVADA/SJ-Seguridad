<?php

namespace App\Models\Matrices;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matrix extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'matrices';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'submission_counter' => 0,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'area_key',
        'owner_user_id',
        'updated_by_user_id',
        'status',
        'current_version_id',
        'submission_counter',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submission_counter' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Usuario que guardó la última edición de la definición.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * @return HasMany<MatrixVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(MatrixVersion::class, 'matrix_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(MatrixVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<MatrixSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(MatrixSubmission::class, 'matrix_id');
    }

    /**
     * @return HasMany<MatrixUserAccess, $this>
     */
    public function userAccess(): HasMany
    {
        return $this->hasMany(MatrixUserAccess::class, 'matrix_id');
    }

    /**
     * @return HasMany<MatrixAreaAccess, $this>
     */
    public function areaAccess(): HasMany
    {
        return $this->hasMany(MatrixAreaAccess::class, 'matrix_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED || $this->archived_at !== null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_PUBLISHED => 'Publicada',
            self::STATUS_ARCHIVED => 'Archivada',
            default => $this->status,
        };
    }

    /**
     * @param  Builder<Matrix>  $query
     * @return Builder<Matrix>
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at')->where('status', '!=', self::STATUS_ARCHIVED);
    }
}
