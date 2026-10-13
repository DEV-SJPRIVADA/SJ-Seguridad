<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Concepto / área del gráfico «Personal por concepto» (Dashboard GH).
 */
class GhAreaDashboardConcepto extends Model
{
    protected $table = 'gh_area_dashboard_conceptos';

    protected $fillable = [
        'name',
        'sort_order',
    ];

    /**
     * @return HasMany<GhAreaDashboardConceptoCargo, $this>
     */
    public function cargos(): HasMany
    {
        return $this->hasMany(GhAreaDashboardConceptoCargo::class, 'concepto_id');
    }
}
