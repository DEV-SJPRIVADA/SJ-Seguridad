<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeleccionCatalogService
{
    /**
     * @return list<string>
     */
    public function managedTypes(): array
    {
        /** @var list<string> $types */
        $types = config('seleccion.managed_catalog_types', []);

        return $types;
    }

    public function isManagedType(string $type): bool
    {
        return in_array($type, $this->managedTypes(), true);
    }

    /**
     * @return array<string, string>
     */
    public function typeLabels(): array
    {
        /** @var array<string, string> $allLabels */
        $allLabels = config('employee_ficha.catalog_type_labels', []);
        $labels = [];

        foreach ($this->managedTypes() as $type) {
            $labels[$type] = $allLabels[$type] ?? $type;
        }

        return $labels;
    }

    /**
     * @return list<array{key: string, label: string, items: Collection<int, PayrollCatalogItem>, columnLabels: array{code: string, name: string}}>
     */
    public function catalogsForAdmin(): array
    {
        $catalogs = [];
        /** @var array<string, array{code: string, name: string}> $columnLabels */
        $columnLabels = config('employee_ficha.catalog_column_labels', []);

        foreach ($this->typeLabels() as $type => $label) {
            $catalogs[] = [
                'key' => $type,
                'label' => $label,
                'items' => PayrollCatalogItem::query()
                    ->ofType($type)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
                'columnLabels' => $columnLabels[$type] ?? ['code' => 'Codigo', 'name' => 'Nombre'],
            ];
        }

        return $catalogs;
    }

    /**
     * Bloquea DELETE si el code está referenciado en Ingreso / Examen ocupacional.
     */
    public function hasBusinessReferences(PayrollCatalogItem $item): bool
    {
        $code = $item->code;
        $type = $item->catalog_type;

        return match ($type) {
            'blood_type' => $this->codeExistsInTable('seleccion_ingresos', 'blood_type_code', $code),
            'marital_status' => $this->codeExistsInTable('seleccion_examenes_ocupacionales', 'marital_status_code', $code),
            'seleccion_solicitud_status' => $this->codeExistsInTable('seleccion_examenes_ocupacionales', 'solicitud_status_code', $code),
            'city' => $this->codeExistsInTable('seleccion_ingresos', 'city_code', $code)
                || $this->codeExistsInTable('seleccion_examenes_ocupacionales', 'city_code', $code),
            'position' => $this->codeExistsInTable('seleccion_ingresos', 'position_code', $code)
                || $this->codeExistsInTable('seleccion_examenes_ocupacionales', 'position_code', $code),
            'eps' => $this->codeExistsInTable('seleccion_examenes_ocupacionales', 'eps_code', $code),
            'afp' => $this->codeExistsInTable('seleccion_examenes_ocupacionales', 'afp_code', $code),
            default => false,
        };
    }

    private function codeExistsInTable(string $table, string $column, string $code): bool
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return false;
        }

        return DB::table($table)->where($column, $code)->exists();
    }
}
