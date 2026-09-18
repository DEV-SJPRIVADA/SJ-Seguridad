<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeCurso;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeCursoDocumentService
{
    public function disk(): string
    {
        return (string) config('cursos.document.disk', 'local');
    }

    public function directory(): string
    {
        return (string) config('cursos.document.directory', 'employee-cursos');
    }

    public function storeOrReplace(EmployeeCurso $curso, UploadedFile $file): EmployeeCurso
    {
        $this->deleteFile($curso);

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($this->directory().'/'.$curso->id, $filename, $this->disk());

        $curso->forceFill([
            'document_path' => $path,
            'document_original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'document_mime' => $file->getMimeType() ?: null,
            'document_size_bytes' => (int) $file->getSize(),
        ])->save();

        return $curso->refresh();
    }

    public function clear(EmployeeCurso $curso): EmployeeCurso
    {
        $this->deleteFile($curso);

        $curso->forceFill([
            'document_path' => null,
            'document_original_name' => null,
            'document_mime' => null,
            'document_size_bytes' => null,
        ])->save();

        return $curso->refresh();
    }

    public function deleteFile(EmployeeCurso $curso): void
    {
        $path = $curso->document_path;

        if (! filled($path)) {
            return;
        }

        $disk = Storage::disk($this->disk());

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    public function absolutePath(EmployeeCurso $curso): ?string
    {
        if (! $curso->hasDocument()) {
            return null;
        }

        return Storage::disk($this->disk())->path((string) $curso->document_path);
    }

    public function sanitizeOriginalName(string $name): string
    {
        $clean = trim(str_replace(['\\', '/'], '', $name));

        if ($clean === '') {
            return 'documento';
        }

        return Str::limit($clean, 255, '');
    }
}
