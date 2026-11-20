<?php

namespace App\Models\Matrices;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatrixSubmission extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'matrix_submissions';

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
        'matrix_version_id',
        'folio',
        'status',
        'created_by_user_id',
        'submitted_at',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Matrix::class, 'matrix_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MatrixVersion::class, 'matrix_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<MatrixSubmissionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(MatrixSubmissionValue::class, 'submission_id');
    }

    /**
     * @return HasMany<MatrixSubmissionAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MatrixSubmissionAttachment::class, 'submission_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED || $this->archived_at !== null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_SUBMITTED => 'Enviado',
            self::STATUS_ARCHIVED => 'Archivado',
            default => $this->status,
        };
    }

    public function isOwnedBy(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        return (int) $this->created_by_user_id === $userId;
    }

    /**
     * Registros visibles según el alcance resuelto para el usuario.
     *
     * @param  Builder<MatrixSubmission>  $query
     * @return Builder<MatrixSubmission>
     */
    public function scopeVisibleTo(Builder $query, bool $canViewAll, int $userId): Builder
    {
        if ($canViewAll) {
            return $query;
        }

        return $query->where('created_by_user_id', $userId);
    }
}
