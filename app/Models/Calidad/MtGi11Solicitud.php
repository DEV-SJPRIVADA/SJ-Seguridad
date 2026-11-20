<?php

namespace App\Models\Calidad;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Solicitud MT-GI-11 (estandarización de un documento del sistema de gestión).
 *
 * El `consecutivo` NO es único: el mismo documento puede tener varias
 * solicitudes/versiones. Toda operación (ver, editar, eliminar, adjuntos)
 * se identifica por `id`.
 */
class MtGi11Solicitud extends Model
{
    use SoftDeletes;

    protected $table = 'calidad_mt_gi_11_solicitudes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fecha_solicitud',
        'created_by',
        'proceso_id',
        'consecutivo',
        'tipo_documento_id',
        'nombre_documento',
        'tipo_solicitud_id',
        'codigo_actual',
        'version_actual',
        'justificacion',
        'responsable_proceso',
        'cargo_responsable_id',
        'codigo_estandarizacion',
        'fecha_estandarizacion',
        'version_actualizada',
        'estado_id',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'date',
            'fecha_estandarizacion' => 'date',
            'version_actualizada' => 'integer',
        ];
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(MtGi11Proceso::class, 'proceso_id');
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(MtGi11TipoDocumento::class, 'tipo_documento_id');
    }

    public function tipoSolicitud(): BelongsTo
    {
        return $this->belongsTo(MtGi11TipoSolicitud::class, 'tipo_solicitud_id');
    }

    public function cargoResponsable(): BelongsTo
    {
        return $this->belongsTo(MtGi11CargoResponsable::class, 'cargo_responsable_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(MtGi11Estado::class, 'estado_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<MtGi11Adjunto, $this>
     */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(MtGi11Adjunto::class, 'solicitud_id')->orderBy('id');
    }

    /**
     * Alcance por registro: sin `view_all` el usuario solo ve lo que solicitó.
     *
     * @param  Builder<MtGi11Solicitud>  $query
     * @return Builder<MtGi11Solicitud>
     */
    public function scopeVisibleTo(Builder $query, int $userId, bool $viewAll): Builder
    {
        if ($viewAll) {
            return $query;
        }

        return $query->where('created_by', $userId);
    }

    public function isOwnedBy(?int $userId): bool
    {
        return $userId !== null && (int) $this->created_by === (int) $userId;
    }

    /**
     * Nombre completo del usuario que solicitó (o su correo si no hay nombre).
     */
    public function solicitanteLabel(): string
    {
        $name = trim((string) ($this->creator?->name ?? ''));

        return $name !== '' ? $name : '—';
    }
}
