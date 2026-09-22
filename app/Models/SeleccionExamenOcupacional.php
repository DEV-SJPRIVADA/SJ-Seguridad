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
