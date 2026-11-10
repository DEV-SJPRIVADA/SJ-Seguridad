<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class ReportesNovedadesIncapacidadAdjunto extends Model
{
    protected $table = 'reportes_novedades_incapacidad_adjuntos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'incapacidad_id',
        'uploaded_by',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'incapacidad_id' => 'integer',
            'uploaded_by' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ReportesNovedadesIncapacidadAdjunto $adjunto): void {
            $disk = (string) config('reportes_novedades.incapacidades_adjuntos.disk', 'local');

            if ($adjunto->stored_path !== '' && Storage::disk($disk)->exists($adjunto->stored_path)) {
                Storage::disk($disk)->delete($adjunto->stored_path);
            }
        });
    }

    public function incapacidad(): BelongsTo
    {
        return $this->belongsTo(ReportesNovedadesIncapacidad::class, 'incapacidad_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sizeLabel(): string
    {
        return Number::fileSize($this->size_bytes);
    }

    public function isImage(): bool
    {
        $mime = strtolower((string) $this->mime_type);
        if (str_starts_with($mime, 'image/')) {
            return true;
        }

        return (bool) preg_match('/\.(jpe?g)$/i', (string) $this->original_name);
    }
}
