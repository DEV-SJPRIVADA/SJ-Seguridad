<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\GhAreaDashboardConcepto;
use App\Models\GhAreaDashboardConceptoCargo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CRUD de conceptos/áreas y asignación de cargos (Dashboard área GH).
 */
final class GhAreaDashboardConceptoService
{
    public const OTROS_LABEL = 'OTROS';

    /**
     * Payload para el modal de administración.
     *
     * @return array{
     *     conceptos: list<array{id: int, name: string, sort_order: int, cargos: list<array{id: int, position_key: string, position_label: string}>}>,
     *     cargos_sin_asignar: list<array{value: string, label: string}>
     * }
     */
    public function catalog(): array
    {
        $conceptos = GhAreaDashboardConcepto::query()
            ->with(['cargos' => static fn ($q) => $q->orderBy('position_label')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(static fn (GhAreaDashboardConcepto $c): array => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'sort_order' => (int) $c->sort_order,
                'cargos' => $c->cargos->map(static fn (GhAreaDashboardConceptoCargo $cargo): array => [
                    'id' => (int) $cargo->id,
                    'position_key' => (string) $cargo->position_key,
                    'position_label' => (string) $cargo->position_label,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $assignedKeys = GhAreaDashboardConceptoCargo::query()
            ->pluck('position_key')
            ->map(static fn ($k): string => (string) $k)
            ->all();

        $assignedLookup = array_fill_keys($assignedKeys, true);

        $sinAsignar = EmployeeFichaProfile::query()
            ->select('position_name')
            ->whereNotNull('position_name')
            ->where('position_name', '!=', '')
            ->distinct()
            ->orderBy('position_name')
            ->pluck('position_name')
            ->map(static function ($name) use ($assignedLookup): ?array {
                $label = trim((string) $name);
                if ($label === '') {
                    return null;
                }
                $key = GhAreaDashboardConceptoCargo::normalizeKey($label);
                if (isset($assignedLookup[$key])) {
                    return null;
                }

                return ['value' => $label, 'label' => $label];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'conceptos' => $conceptos,
            'cargos_sin_asignar' => $sinAsignar,
        ];
    }

    /**
     * Mapa position_key → nombre de concepto.
     *
     * @return array<string, string>
     */
    public function positionKeyToConceptoName(): array
    {
        return GhAreaDashboardConceptoCargo::query()
            ->join('gh_area_dashboard_conceptos', 'gh_area_dashboard_conceptos.id', '=', 'gh_area_dashboard_concepto_cargos.concepto_id')
            ->pluck('gh_area_dashboard_conceptos.name', 'gh_area_dashboard_concepto_cargos.position_key')
            ->mapWithKeys(static fn ($name, $key): array => [(string) $key => (string) $name])
            ->all();
    }

    public function createConcepto(string $name): GhAreaDashboardConcepto
    {
        $name = $this->normalizeConceptoName($name);
        $this->assertConceptoNameAvailable($name);

        $maxSort = (int) GhAreaDashboardConcepto::query()->max('sort_order');

        return GhAreaDashboardConcepto::query()->create([
            'name' => $name,
            'sort_order' => $maxSort + 1,
        ]);
    }

    public function updateConcepto(GhAreaDashboardConcepto $concepto, string $name): GhAreaDashboardConcepto
    {
        $name = $this->normalizeConceptoName($name);
        $this->assertConceptoNameAvailable($name, $concepto->id);
        $concepto->update(['name' => $name]);

        return $concepto->refresh();
    }

    public function deleteConcepto(GhAreaDashboardConcepto $concepto): void
    {
        $concepto->delete();
    }

    public function assignCargo(GhAreaDashboardConcepto $concepto, string $positionName): GhAreaDashboardConceptoCargo
    {
        $label = trim($positionName);
        if ($label === '') {
            throw ValidationException::withMessages([
                'position_name' => 'Indique un cargo.',
            ]);
        }

        $key = GhAreaDashboardConceptoCargo::normalizeKey($label);

        return DB::transaction(function () use ($concepto, $key, $label): GhAreaDashboardConceptoCargo {
            $existing = GhAreaDashboardConceptoCargo::query()->where('position_key', $key)->first();
            if ($existing) {
                $existing->update([
                    'concepto_id' => $concepto->id,
                    'position_label' => $label,
                ]);

                return $existing->refresh();
            }

            return GhAreaDashboardConceptoCargo::query()->create([
                'concepto_id' => $concepto->id,
                'position_key' => $key,
                'position_label' => $label,
            ]);
        });
    }

    public function unassignCargo(GhAreaDashboardConceptoCargo $cargo): void
    {
        $cargo->delete();
    }

    private function normalizeConceptoName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Indique el nombre del concepto.',
            ]);
        }

        if (GhAreaDashboardConceptoCargo::normalizeKey($name) === self::OTROS_LABEL) {
            throw ValidationException::withMessages([
                'name' => 'El concepto «OTROS» es reservado (cargos sin asignar).',
            ]);
        }

        return mb_strtoupper($name, 'UTF-8');
    }

    private function assertConceptoNameAvailable(string $name, ?int $ignoreId = null): void
    {
        $query = GhAreaDashboardConcepto::query()->where('name', $name);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un concepto con ese nombre.',
            ]);
        }
    }
}
