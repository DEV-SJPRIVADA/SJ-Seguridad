<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asignación cargo (Ficha) → concepto del Dashboard área GH.
 */
class GhAreaDashboardConceptoCargo extends Model
{
    protected $table = 'gh_area_dashboard_concepto_cargos';

    protected $fillable = [
        'concepto_id',
        'position_key',
        'position_label',
    ];

    /**
     * @return BelongsTo<GhAreaDashboardConcepto, $this>
     */
    public function concepto(): BelongsTo
    {
        return $this->belongsTo(GhAreaDashboardConcepto::class, 'concepto_id');
    }

    public static function normalizeKey(string $positionName): string
    {
        return mb_strtoupper(trim($positionName), 'UTF-8');
    }
}
