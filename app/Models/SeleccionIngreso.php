<?php

namespace App\Models;

use Database\Factories\SeleccionIngresoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeleccionIngreso extends Model
{
    /** @use HasFactory<SeleccionIngresoFactory> */
    use HasFactory;

    protected $table = 'seleccion_ingresos';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_number',
        'full_name',
        'email',
        'phone',
        'city_code',
        'city_name',
        'position_code',
        'position_name',
        'commercial_client_id',
        'shirt_size',
        'pants_size',
        'shoes_size',
        'requisition_uniform_id',
        'fecha_ingreso',
        'blood_type_code',
        'blood_type_name',
        'reemplaza_a',
        'responsable_user_id',
        'jefe_ope',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'commercial_client_id' => 'integer',
            'requisition_uniform_id' => 'integer',
            'responsable_user_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function commercialClient(): BelongsTo
    {
        return $this->belongsTo(CommercialClient::class, 'commercial_client_id');
    }

    public function uniform(): BelongsTo
    {
        return $this->belongsTo(RequisitionUniform::class, 'requisition_uniform_id');
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
