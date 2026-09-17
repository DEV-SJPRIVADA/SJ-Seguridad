<?php

namespace App\Services\DevelopmentRequests;

use App\Models\DevelopmentRequest;
use App\Models\DevelopmentRequestAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DevelopmentRequestAttachmentService
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
     * @param  array<int, bool>  $requiredFlags  keyed by file index
     */
    public function storeMany(DevelopmentRequest $request, array $files, array $requiredFlags = [], int $startSortOrder = 1): void
    {
        foreach (array_values($files) as $offset => $file) {
            $this->storeOne(
                $request,
                $file,
                $startSortOrder + $offset,
                (bool) ($requiredFlags[$offset] ?? false),
            );
        }
    }

    public function storeOne(
        DevelopmentRequest $request,
        UploadedFile $file,
        int $sortOrder,
        bool $isRequiredToUnderstand = false,
    ): DevelopmentRequestAttachment {
        $disk = (string) config('development-requests.attachments.disk', 'local');
        $directory = (string) config('development-requests.attachments.directory', 'development-requests');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($directory.'/'.$request->id, $filename, $disk);

        return $request->attachments()->create([
            'uploaded_by' => auth()->id(),
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType() ?: null,
            'size_bytes' => (int) $file->getSize(),
            'is_required_to_understand' => $isRequiredToUnderstand,
            'sort_order' => $sortOrder,
        ]);
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = trim(str_replace(["\0", '/', '\\'], '', $name));

        return $name !== '' ? mb_substr($name, 0, 255) : 'archivo';
    }
}
