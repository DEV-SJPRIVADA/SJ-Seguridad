<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
     * Catálogos que no exponen Código ni Orden en la UI (código autogenerado).
     */
    public function isCodeless(string $type): bool
    {
        /** @var list<string> $types */
        $types = config('seleccion.codeless_catalog_types', []);

        return in_array($type, $types, true);
    }

    /**
     * Código técnico único dentro del tipo, derivado del nombre
     * (ej. «Unión libre» → UNION_LIBRE; conserva + y - para RH).
     */
    public function makeUniqueCode(string $type, string $name): string
    {
        $normalized = Str::upper(Str::ascii(trim($name)));
        $normalized = (string) preg_replace('/\s+/', '_', $normalized);
        $normalized = (string) preg_replace('/[^A-Z0-9_+\-]/', '', $normalized);
        $base = $normalized !== '' ? mb_substr($normalized, 0, 45) : 'ITEM';

        $code = $base;
        $i = 2;

        while (PayrollCatalogItem::query()->ofType($type)->where('code', $code)->exists()) {
            $suffix = '_'.$i;
            $code = mb_substr($base, 0, 50 - strlen($suffix)).$suffix;
            $i++;
        }

        return $code;
    }

    /**
     * Orden siguiente al final del catálogo (para tipos sin campo Orden).
     */
    public function nextSortOrder(string $type): int
    {
        return ((int) PayrollCatalogItem::query()->ofType($type)->max('sort_order')) + 1;
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
     * @return list<array{key: string, label: string, codeless: bool, items: Collection<int, PayrollCatalogItem>, columnLabels: array{code: string, name: string}}>
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
                'codeless' => $this->isCodeless($type),
                'items' => PayrollCatalogItem::query()
                    ->ofType($type)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
                'columnLabels' => $columnLabels[$type] ?? ['code' => 'Código', 'name' => 'Nombre'],
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
            'ingreso_estado' => $this->codeExistsInTable('seleccion_ingresos', 'estado_code', $code),
            'jefe_operativo' => $this->codeExistsInTable('seleccion_ingresos', 'jefe_ope_code', $code),
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
