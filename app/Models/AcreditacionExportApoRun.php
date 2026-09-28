<?php

namespace App\Models;

use Database\Factories\AcreditacionExportApoRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcreditacionExportApoRun extends Model
{
    /** @use HasFactory<AcreditacionExportApoRunFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'acreditacion_export_apo_runs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'export_date',
        'seq',
        'file_name',
        'user_id',
        'vigencia_policy',
        'include_novedades',
        'rows_selected',
        'rows_ok',
        'rows_novedad',
        'rows_blocked',
        'rows_exported',
    ];

    protected $attributes = [
        'include_novedades' => false,
        'rows_selected' => 0,
        'rows_ok' => 0,
        'rows_novedad' => 0,
        'rows_blocked' => 0,
        'rows_exported' => 0,
    ];

    protected function casts(): array
    {
        return [
            'export_date' => 'date',
            'seq' => 'integer',
            'include_novedades' => 'boolean',
            'rows_selected' => 'integer',
            'rows_ok' => 'integer',
            'rows_novedad' => 'integer',
            'rows_blocked' => 'integer',
            'rows_exported' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
