<?php

namespace App\Models;

use Database\Factories\DevelopmentRequestAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class DevelopmentRequestAttachment extends Model
{
    /** @use HasFactory<DevelopmentRequestAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'development_request_id',
        'uploaded_by',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
        'is_required_to_understand',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
            'is_required_to_understand' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (DevelopmentRequestAttachment $attachment): void {
            $disk = (string) config('development-requests.attachments.disk', 'local');

            if ($attachment->stored_path !== '' && Storage::disk($disk)->exists($attachment->stored_path)) {
                Storage::disk($disk)->delete($attachment->stored_path);
            }
        });
    }

    public function developmentRequest(): BelongsTo
    {
        return $this->belongsTo(DevelopmentRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sizeLabel(): string
    {
        return Number::fileSize($this->size_bytes);
    }
}
