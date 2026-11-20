<?php

namespace App\Models\Matrices;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatrixVersion extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $table = 'matrix_versions';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'matrix_id',
        'version_number',
        'status',
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
            'published_at' => 'datetime',
        ];
    }

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Matrix::class, 'matrix_id');
    }

    /**
     * @return HasMany<MatrixField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(MatrixField::class, 'matrix_version_id')->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasMany<MatrixSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(MatrixSubmission::class, 'matrix_version_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_PUBLISHED => 'Publicada',
            default => $this->status,
        };
    }
}
