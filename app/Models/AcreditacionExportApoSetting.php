<?php

namespace App\Models;

use Database\Factories\AcreditacionExportApoSettingFactory;
use Database\Seeders\AcreditacionExportApoSettingSeeder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcreditacionExportApoSetting extends Model
{
    /** @use HasFactory<AcreditacionExportApoSettingFactory> */
    use HasFactory;

    protected $table = 'acreditacion_export_apo_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nit',
        'razon_social',
        'tipo_documento',
        'tipo_establecimiento',
        'telefono_r',
        'direccion_r',
        'direccion_p',
        'departamento',
        'ciudad',
        'educacion_bm',
        'educacion_s',
        'discapacidad',
        'updated_by',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Fila única de parámetros (crea con defaults del seeder si no existe).
     */
    public static function singleton(): self
    {
        $existing = static::query()->orderBy('id')->first();

        if ($existing !== null) {
            return $existing;
        }

        return static::query()->create(
            AcreditacionExportApoSettingSeeder::defaultAttributes()
        );
    }
}
