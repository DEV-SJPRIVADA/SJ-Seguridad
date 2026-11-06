<?php

namespace App\Services\Calidad;

use App\Models\ServicioClienteCaso;
use App\Models\ServicioClienteCasoAdjunto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServicioClienteAdjuntoService
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<ServicioClienteCasoAdjunto>
     */
    public function storeMany(ServicioClienteCaso $caso, array $files, int $userId): array
    {
        $stored = [];

        foreach (array_values($files) as $file) {
            $stored[] = $this->storeOne($caso, $file, $userId);
        }

        return $stored;
    }

    public function storeOne(ServicioClienteCaso $caso, UploadedFile $file, int $userId): ServicioClienteCasoAdjunto
    {
        $disk = (string) config('servicio_cliente.adjuntos.disk', 'local');
        $directory = (string) config('servicio_cliente.adjuntos.directory', 'servicio-cliente/fo-gi-22');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($directory.'/'.$caso->id, $filename, $disk);

        return $caso->adjuntos()->create([
            'uploaded_by' => $userId,
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType() ?: null,
            'size_bytes' => (int) $file->getSize(),
        ]);
    }

    public function delete(ServicioClienteCasoAdjunto $adjunto): void
    {
        $disk = (string) config('servicio_cliente.adjuntos.disk', 'local');
        Storage::disk($disk)->delete($adjunto->stored_path);
        $adjunto->delete();
    }

    public function deleteAll(ServicioClienteCaso $caso): void
    {
        $disk = (string) config('servicio_cliente.adjuntos.disk', 'local');

        foreach ($caso->adjuntos as $adjunto) {
            Storage::disk($disk)->delete($adjunto->stored_path);
        }
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
