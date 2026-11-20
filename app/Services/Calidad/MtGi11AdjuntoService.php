<?php

namespace App\Services\Calidad;

use App\Models\Calidad\MtGi11Adjunto;
use App\Models\Calidad\MtGi11Solicitud;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Adjuntos MT-GI-11: disco privado `local`, carpeta por solicitud.
 */
final class MtGi11AdjuntoService
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<MtGi11Adjunto>
     */
    public function storeMany(MtGi11Solicitud $solicitud, array $files, int $userId): array
    {
        $stored = [];

        foreach (array_values($files) as $file) {
            $stored[] = $this->storeOne($solicitud, $file, $userId);
        }

        return $stored;
    }

    public function storeOne(MtGi11Solicitud $solicitud, UploadedFile $file, int $userId): MtGi11Adjunto
    {
        $disk = (string) config('mt_gi_11.adjuntos.disk', 'local');
        $directory = (string) config('mt_gi_11.adjuntos.directory', 'calidad/mt-gi-11');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($directory.'/'.$solicitud->id, $filename, $disk);

        return $solicitud->adjuntos()->create([
            'uploaded_by' => $userId,
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType() ?: null,
            'size_bytes' => (int) $file->getSize(),
        ]);
    }

    public function delete(MtGi11Adjunto $adjunto): void
    {
        $disk = (string) config('mt_gi_11.adjuntos.disk', 'local');
        Storage::disk($disk)->delete($adjunto->stored_path);
        $adjunto->delete();
    }

    public function deleteAll(MtGi11Solicitud $solicitud): void
    {
        $disk = (string) config('mt_gi_11.adjuntos.disk', 'local');

        foreach ($solicitud->adjuntos()->get() as $adjunto) {
            Storage::disk($disk)->delete($adjunto->stored_path);
            $adjunto->delete();
        }
    }

    /**
     * Limpia el nombre original para mostrarlo y descargarlo sin rutas ni caracteres de control.
     */
    private function sanitizeOriginalName(string $name): string
    {
        $name = str_replace(['\\', '/'], '-', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?? $name;
        $name = trim($name);

        return $name === '' ? 'archivo' : mb_substr($name, 0, 255);
    }
}
