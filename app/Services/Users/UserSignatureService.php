<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Firma manuscrita vigente del usuario (PNG en disco privado).
 * Las vistas reutilizan dataUri(); los documentos oficiales copian con copyFromUser().
 */
class UserSignatureService
{
    public function disk(): string
    {
        return (string) config('user-signatures.disk', 'local');
    }

    public function store(User $user, UploadedFile $file): string
    {
        $this->deleteFile($user->signature_path);

        $directory = trim((string) config('user-signatures.directory', 'user-signatures'), '/').'/'.$user->id;
        $path = $file->storeAs($directory, Str::uuid()->toString().'.png', $this->disk());

        $user->forceFill(['signature_path' => $path])->save();

        return $path;
    }

    public function delete(User $user): void
    {
        $this->deleteFile($user->signature_path);
        $user->forceFill(['signature_path' => null])->save();
    }

    public function dataUri(?User $user): ?string
    {
        return $this->dataUriFromPath($user?->signature_path);
    }

    public function dataUriFromPath(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $disk = Storage::disk($this->disk());

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($contents);
    }

    public function copyFromUser(?User $user, string $destination): ?string
    {
        if ($user === null || ! filled($user->signature_path)) {
            return null;
        }

        $disk = Storage::disk($this->disk());

        if (! $disk->exists($user->signature_path)) {
            return null;
        }

        $contents = $disk->get($user->signature_path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        $disk->put($destination, $contents);

        return $destination;
    }

    public function has(?User $user): bool
    {
        return $this->dataUri($user) !== null;
    }

    private function deleteFile(?string $path): void
    {
        if (! filled($path)) {
            return;
        }

        $disk = Storage::disk($this->disk());

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
