<?php

namespace App\Models\Matrices;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatrixSubmissionAttachment extends Model
{
    public const PREVIEWABLE_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    protected $table = 'matrix_submission_attachments';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'field_id',
        'uploaded_by_user_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(MatrixSubmission::class, 'submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(MatrixField::class, 'field_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function isPreviewable(): bool
    {
        return in_array((string) $this->mime_type, self::PREVIEWABLE_MIME_TYPES, true);
    }

    public function extension(): string
    {
        $extension = pathinfo((string) $this->original_name, PATHINFO_EXTENSION);

        return $extension !== '' ? strtolower($extension) : 'file';
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.').' KB';
        }

        return $bytes.' B';
    }
}
