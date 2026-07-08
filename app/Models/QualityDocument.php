<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class QualityDocument extends Model
{
    use HasFactory;

    public const TYPE_FILE = 'file';

    public const TYPE_LINK = 'link';

    protected $fillable = [
        'title',
        'code',
        'root_process',
        'document_type',
        'description',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'external_url',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function areas(): HasMany
    {
        return $this->hasMany(QualityDocumentArea::class);
    }

    public function assignedUsers(): HasMany
    {
        return $this->hasMany(QualityDocumentUser::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForArea(Builder $query, string $areaKey): Builder
    {
        return $query->whereHas('areas', fn (Builder $inner) => $inner->where('area_key', $areaKey));
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('assignedUsers', fn (Builder $inner) => $inner->where('user_id', $userId));
    }

    public function assignedAreaKeys(): array
    {
        return $this->areas()->pluck('area_key')->all();
    }

    public function assignedUserIds(): array
    {
        return $this->assignedUsers()->pluck('user_id')->all();
    }

    public function isAssignedToArea(string $areaKey): bool
    {
        return in_array($areaKey, $this->assignedAreaKeys(), true);
    }

    public function isAssignedToUser(int $userId): bool
    {
        return in_array($userId, $this->assignedUserIds(), true);
    }

    public function rootProcessLabel(): ?string
    {
        if (! $this->root_process) {
            return null;
        }

        return config("access.areas.{$this->root_process}", $this->root_process);
    }

    public function documentTypeLabel(): ?string
    {
        if (! $this->document_type) {
            return null;
        }

        return config("access.quality_document_types.{$this->document_type}", $this->document_type);
    }

    public function isFile(): bool
    {
        return $this->type === self::TYPE_FILE;
    }

    public function isLink(): bool
    {
        return $this->type === self::TYPE_LINK;
    }

    public static function tablesReady(): bool
    {
        return Schema::hasTable('quality_documents')
            && Schema::hasTable('quality_document_areas')
            && Schema::hasTable('quality_document_users');
    }

    public static function hasActiveForUser(int $userId): bool
    {
        if (! static::tablesReady()) {
            return false;
        }

        return static::query()->active()->forUser($userId)->exists();
    }
}
