<?php

namespace App\Models\Calidad;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adjunto de una solicitud MT-GI-11 (disco privado, descarga autorizada).
 */
class MtGi11Adjunto extends Model
{
    protected $table = 'calidad_mt_gi_11_adjuntos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'solicitud_id',
        'uploaded_by',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(MtGi11Solicitud::class, 'solicitud_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Vista previa solo para PDF e imágenes. */
    public function isPreviewable(): bool
    {
        $mime = (string) $this->mime_type;

        return $mime === 'application/pdf' || str_starts_with($mime, 'image/');
    }

    /** Etiqueta legible del tamaño (B / KB / MB). */
    public function sizeLabel(): string
    {
        $bytes = max(0, (int) $this->size_bytes);

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
