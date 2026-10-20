<?php

namespace App\Models;

use Database\Factories\SeleccionExamenOcupacionalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeleccionExamenOcupacional extends Model
{
    /** @use HasFactory<SeleccionExamenOcupacionalFactory> */
    use HasFactory;

    protected $table = 'seleccion_examenes_ocupacionales';

    public const ESTADO_SOLICITADO = 'solicitado';

    public const ESTADO_AGENDADO = 'agendado';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_NO_APROBADO = 'no_aprobado';

    public const ESTADO_CANCELADO = 'cancelado';

    /**
     * Estados operativos del examen (orden = flujo) → label UI.
     *
     * @var array<string, string>
     */
    public const ESTADO_LABELS = [
        self::ESTADO_SOLICITADO => 'SOLICITADO',
        self::ESTADO_AGENDADO => 'AGENDADO',
        self::ESTADO_APROBADO => 'APROBADO',
        self::ESTADO_NO_APROBADO => 'NO APROBADO',
        self::ESTADO_CANCELADO => 'CANCELADO',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado' => self::ESTADO_SOLICITADO,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'full_name',
        'position_code',
        'position_name',
        'servicio_sector',
        'commercial_client_id',
        'eps_code',
        'eps_name',
        'afp_code',
        'afp_name',
        'birth_date',
        'city_code',
        'city_name',
        'address',
        'email',
        'phone',
        'marital_status_code',
        'marital_status_name',
        'fecha_arl',
        'estado',
        'solicitud_status_code',
        'solicitud_status_name',
        'responsable_user_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'fecha_arl' => 'date',
            'commercial_client_id' => 'integer',
            'responsable_user_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function estadoLabel(): string
    {
        return self::ESTADO_LABELS[$this->estado] ?? mb_strtoupper((string) $this->estado);
    }

    public function commercialClient(): BelongsTo
    {
        return $this->belongsTo(CommercialClient::class, 'commercial_client_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
