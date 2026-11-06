<?php

namespace App\Services\GestionHumana;

use App\Models\ReportesNovedadesIncapacidad;
use App\Models\ReportesNovedadesIncapacidadAdjunto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ReportesNovedadesIncapacidadAdjuntoService
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<ReportesNovedadesIncapacidadAdjunto>
     */
    public function storeMany(ReportesNovedadesIncapacidad $incapacidad, array $files, int $userId): array
    {
        $stored = [];

        foreach (array_values($files) as $file) {
            $stored[] = $this->storeOne($incapacidad, $file, $userId);
        }

        return $stored;
    }

    public function storeOne(ReportesNovedadesIncapacidad $incapacidad, UploadedFile $file, int $userId): ReportesNovedadesIncapacidadAdjunto
    {
        $disk = (string) config('reportes_novedades.incapacidades_adjuntos.disk', 'local');
        $directory = (string) config('reportes_novedades.incapacidades_adjuntos.directory', 'reportes-novedades/incapacidades');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $path = $file->storeAs($directory.'/'.$incapacidad->id, $filename, $disk);

        return $incapacidad->adjuntos()->create([
            'uploaded_by' => $userId,
            'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType() ?: null,
            'size_bytes' => (int) $file->getSize(),
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
