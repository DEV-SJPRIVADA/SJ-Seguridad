<?php

namespace App\Services\Calidad\Planeacion;

use App\Models\QualityPlanning\QualityPlanningIndicator;
use App\Models\QualityPlanning\QualityPlanningIndicatorSubstitution;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Suplencias de indicadores: el suplente captura a nombre del titular dentro del rango.
 *
 * Regla acordada: el suplente solo captura/reemplaza durante el rango y no hereda consulta.
 */
class PlaneacionSubstitutionService
{
    /**
     * Suplencia vigente del usuario para el indicador (una sola por indicador y fecha).
     */
    public function activeFor(QualityPlanningIndicator $indicator, User $user, ?CarbonImmutable $on = null): ?QualityPlanningIndicatorSubstitution
    {
        $date = ($on ?? CarbonImmutable::now())->toDateString();

        return $indicator->substitutions()
            ->with('holder')
            ->where('substitute_user_id', $user->id)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->orderBy('starts_on')
            ->first();
    }

    /**
     * ¿La fecha cae en el rango de la suplencia?
     */
    public function covers(QualityPlanningIndicatorSubstitution $substitution, int $year, int $month): bool
    {
        $first = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $last = $first->endOfMonth();

        return $substitution->starts_on->lessThanOrEqualTo($last)
            && $substitution->ends_on->greaterThanOrEqualTo($first);
    }

    /**
     * Usuario al que se atribuye la captura: titular si hay suplencia que cubre el mes.
     */
    public function captureOwner(
        QualityPlanningIndicator $indicator,
        User $actor,
        int $year,
        int $month,
    ): User {
        $substitution = $this->activeFor($indicator, $actor);

        if ($substitution instanceof QualityPlanningIndicatorSubstitution
            && $this->covers($substitution, $year, $month)
            && $substitution->holder instanceof User) {
            return $substitution->holder;
        }

        return $actor;
    }

    /**
     * @return Collection<int, QualityPlanningIndicatorSubstitution>
     */
    public function forIndicator(QualityPlanningIndicator $indicator): Collection
    {
        return $indicator->substitutions()
            ->with(['holder:id,name', 'substitute:id,name'])
            ->orderBy('starts_on')
            ->get();
    }
}
