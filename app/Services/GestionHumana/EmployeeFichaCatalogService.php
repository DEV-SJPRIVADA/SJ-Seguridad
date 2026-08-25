<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
use Illuminate\Support\Collection;

class EmployeeFichaCatalogService
{
    /**
     * @return array<string, string>
     */
    public function typeLabels(): array
    {
        /** @var array<string, string> $labels */
        $labels = config('employee_ficha.catalog_type_labels', []);

        return $labels;
    }

    /**
     * @return list<string>
     */
    public function typeKeys(): array
    {
        return array_keys($this->typeLabels());
    }

    public function isValidType(string $type): bool
    {
        return array_key_exists($type, $this->typeLabels());
    }

    /**
     * @return array<string, list<array{code: string, name: string}>>
     */
    public function optionsForForms(): array
    {
        $options = [];

        foreach ($this->typeKeys() as $type) {
            $options[$type] = PayrollCatalogItem::query()
                ->ofType($type)
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['code', 'name'])
                ->map(fn (PayrollCatalogItem $item): array => ['code' => $item->code, 'name' => $item->name])
                ->all();
        }

        return $options;
    }

    /**
     * @return list<array{key: string, label: string, items: Collection<int, PayrollCatalogItem>, columnLabels: array{code: string, name: string}}>
     */
    public function catalogsForAdmin(): array
    {
        $catalogs = [];
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
}
