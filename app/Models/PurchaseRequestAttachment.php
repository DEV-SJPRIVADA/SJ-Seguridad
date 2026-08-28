<?php

namespace App\Models;

use Database\Factories\PurchaseRequestAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class PurchaseRequestAttachment extends Model
{
    /** @use HasFactory<PurchaseRequestAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (PurchaseRequestAttachment $attachment): void {
            $disk = (string) config('purchase-requests.attachments.disk', 'local');

            if ($attachment->stored_path !== '' && Storage::disk($disk)->exists($attachment->stored_path)) {
                Storage::disk($disk)->delete($attachment->stored_path);
            }
        });
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function sizeLabel(): string
    {
        return Number::fileSize($this->size_bytes);
    }
}
