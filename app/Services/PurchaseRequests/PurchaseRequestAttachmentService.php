<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PurchaseRequestAttachmentService
{
    /**
     * @return list<UploadedFile>
     */
    public function filesFromRequest(Request $request): array
    {
        $files = $request->file('attachments');

        if ($files === null) {
            return [];
        }

        $files = is_array($files) ? $files : [$files];

        return array_values(array_filter(
            $files,
            fn ($file): bool => $file instanceof UploadedFile,
        ));
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function storeMany(PurchaseRequest $purchaseRequest, array $files, int $startSortOrder = 1): void
    {
        foreach (array_values($files) as $offset => $file) {
            $this->storeOne($purchaseRequest, $file, $startSortOrder + $offset);
        }
    }

    public function storeOne(PurchaseRequest $purchaseRequest, UploadedFile $file, int $sortOrder): PurchaseRequestAttachment
    {
        $disk = (string) config('purchase-requests.attachments.disk', 'local');
        $directory = (string) config('purchase-requests.attachments.directory', 'purchase-requests');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($directory.'/'.$purchaseRequest->id, $filename, $disk);

        return $purchaseRequest->attachments()->create([
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType() ?: null,
            'size_bytes' => (int) $file->getSize(),
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  list<int|string>  $keepIds
     * @param  list<UploadedFile>  $newFiles
     */
    public function syncOnResubmit(PurchaseRequest $purchaseRequest, array $keepIds, array $newFiles): void
    {
        $keepIds = array_values(array_unique(array_map('intval', $keepIds)));

        $toDelete = $keepIds === []
            ? $purchaseRequest->attachments()->get()
            : $purchaseRequest->attachments()->whereNotIn('id', $keepIds)->get();

        foreach ($toDelete as $attachment) {
            $attachment->delete();
        }

        $kept = $purchaseRequest->attachments()->orderBy('sort_order')->orderBy('id')->get();

        foreach ($kept as $index => $attachment) {
            $attachment->update(['sort_order' => $index + 1]);
        }

        $this->storeMany($purchaseRequest, $newFiles, $kept->count() + 1);
    }

    public function recordMappedLegacy(PurchaseRequest $purchaseRequest, string $legacyPath): PurchaseRequestAttachment
    {
        $originalName = $this->sanitizeOriginalName(basename($legacyPath));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $directory = (string) config('purchase-requests.attachments.directory', 'purchase-requests');
        $storedPath = $directory.'/'.$purchaseRequest->id.'/'.Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');

        return $purchaseRequest->attachments()->create([
            'original_name' => $originalName,
            'stored_path' => $storedPath,
            'mime_type' => null,
            'size_bytes' => 0,
            'sort_order' => ((int) $purchaseRequest->attachments()->max('sort_order')) + 1,
        ]);
    }

    public function sanitizeOriginalName(string $name): string
    {
        $clean = trim(str_replace(['\\', '/'], '', $name));

        if ($clean === '') {
            return 'archivo';
        }

        return Str::limit($clean, 255, '');
    }
}
