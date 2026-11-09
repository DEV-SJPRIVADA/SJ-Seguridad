<?php

namespace App\Http\Controllers\Supplies;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Services\Supplies\SupplyAuditLogService;
use App\Services\Supplies\SupplySeguimientoService;
use App\Traits\HasSupplyTabs;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplySeguimientoController extends Controller
{
    use HasSupplyTabs;

    public function __construct(
        private readonly SupplySeguimientoService $seguimiento,
        private readonly SupplyAuditLogService $auditLogService,
    ) {}

    public function historial(Request $request, string $module): View
    {
        $this->assertComprasHome($module);
        $filters = $this->filtersFrom($request);
        $matrix = $this->seguimiento->historialMatrix($filters);

        return view('modules.supplies.seguimiento.historial', [
            'module' => $module,
            'subTabs' => $this->getSupplySubTabs($module),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions($filters),
            'monthLabels' => SupplySeguimientoService::MONTH_LABELS,
            'matrix' => $matrix,
        ]);
    }

    public function ordenes(Request $request, string $module): View
    {
        $this->assertComprasHome($module);
        $filters = $this->filtersFrom($request);
        $productKey = $filters['product'];
        $productName = $productKey ? $this->seguimiento->productNameByKey($productKey, $filters) : null;
        $orders = ($productKey && $productName !== null)
            ? $this->seguimiento->productOrders($productKey, $filters)
            : [];
        $orderTotals = [
            'cantidad' => array_sum(array_column($orders, 'cantidad')),
            'valor' => array_sum(array_column($orders, 'valor')),
        ];

        return view('modules.supplies.seguimiento.ordenes', [
            'module' => $module,
            'subTabs' => $this->getSupplySubTabs($module),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions($filters),
            'productKey' => $productKey,
            'productName' => $productName,
            'orders' => $orders,
            'orderTotals' => $orderTotals,
            'monthLabel' => ($filters['month'] ?? null)
                ? (SupplySeguimientoService::MONTH_NAMES[$filters['month']] ?? null)
                : null,
        ]);
    }

    public function historialExport(Request $request, string $module): StreamedResponse
    {
        $this->assertComprasHome($module);
        $filters = $this->filtersFrom($request);
        $matrix = $this->seguimiento->historialMatrix($filters);

        $columns = [
            ['key' => 'name', 'label' => 'Producto'],
        ];
        foreach (SupplySeguimientoService::MONTH_LABELS as $month => $label) {
            $columns[] = ['key' => "m{$month}_inv", 'label' => "{$label} inv."];
            $columns[] = ['key' => "m{$month}_sol", 'label' => "{$label} sol."];
            $columns[] = ['key' => "m{$month}_aut", 'label' => "{$label} aut."];
        }
        $columns[] = ['key' => 'year_inventory', 'label' => 'Último inventario'];
        $columns[] = ['key' => 'year_requested', 'label' => 'Total solicitado'];
        $columns[] = ['key' => 'year_authorized', 'label' => 'Total autorizado'];

        $rows = collect($matrix['rows'])->map(function (array $row): array {
            $flat = [
                'name' => $row['name'],
                'year_inventory' => $row['year_inventory'],
                'year_requested' => $row['year_requested'],
                'year_authorized' => $row['year_authorized'],
            ];
            foreach ($row['months'] as $month => $cell) {
                $flat["m{$month}_inv"] = $cell['inventory'];
                $flat["m{$month}_sol"] = $cell['requested'] ?: null;
                $flat["m{$month}_aut"] = $cell['authorized'] ?: null;
            }

            return $flat;
        });

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'seguimiento_historial_excel',
            metadata: [
                'row_count' => $rows->count(),
                'year' => $filters['year'],
                'area_key' => $filters['area_key'],
                'sede_id' => $filters['sede_id'],
            ],
            area: $module,
        );

        return (new BaseExport(
            $rows,
            $columns,
            'seguimiento_suministros_historial_'.$filters['year'].'.xlsx',
            'Historial de suministros '.$filters['year'],
        ))->download();
    }

    public function gasto(Request $request, string $module): View
    {
        $this->assertComprasHome($module);
        $filters = $this->filtersFrom($request);
        $matrix = $this->seguimiento->gastoMatrix($filters);

        return view('modules.supplies.seguimiento.gasto', [
            'module' => $module,
            'subTabs' => $this->getSupplySubTabs($module),
            'filters' => $filters,
            'filterOptions' => $this->filterOptions($filters),
            'monthLabels' => SupplySeguimientoService::MONTH_LABELS,
            'matrix' => $matrix,
        ]);
    }

    public function gastoExport(Request $request, string $module): StreamedResponse
    {
        $this->assertComprasHome($module);
        $filters = $this->filtersFrom($request);
        $matrix = $this->seguimiento->gastoMatrix($filters);

        $columns = [
            ['key' => 'name', 'label' => 'Producto'],
        ];
        foreach (SupplySeguimientoService::MONTH_LABELS as $month => $label) {
            $columns[] = ['key' => "m{$month}_sol", 'label' => "{$label} sol."];
            $columns[] = ['key' => "m{$month}_comp", 'label' => "{$label} comprado"];
            $columns[] = ['key' => "m{$month}_val", 'label' => "{$label} valor"];
        }
        $columns[] = ['key' => 'year_requested', 'label' => 'Total solicitado'];
        $columns[] = ['key' => 'year_purchased', 'label' => 'Total comprado'];
        $columns[] = ['key' => 'year_value', 'label' => 'Valor (IVA incluido)'];

        $rows = collect($matrix['rows'])->map(function (array $row): array {
            $flat = [
                'name' => $row['name'],
                'year_requested' => $row['year_requested'],
                'year_purchased' => $row['year_purchased'],
                'year_value' => $row['year_value'],
            ];
            foreach ($row['months'] as $month => $cell) {
                $flat["m{$month}_sol"] = $cell['requested'] ?: null;
                $flat["m{$month}_comp"] = $cell['purchased'] ?: null;
                $flat["m{$month}_val"] = $cell['value'] ?: null;
            }

            return $flat;
        });

        $this->auditLogService->logEvent(
            eventType: 'export',
            action: 'seguimiento_gasto_excel',
            metadata: [
                'row_count' => $rows->count(),
                'year' => $filters['year'],
                'area_key' => $filters['area_key'],
                'sede_id' => $filters['sede_id'],
            ],
            area: $module,
        );

        return (new BaseExport(
            $rows,
            $columns,
            'seguimiento_suministros_gasto_'.$filters['year'].'.xlsx',
            'Gasto de suministros '.$filters['year'],
        ))->download();
    }

    private function assertComprasHome(string $module): void
    {
        abort_unless($module === 'compras', 404);
    }

    /**
     * @return array{year: int, month: ?int, area_key: ?string, sede_id: ?int, product: ?string}
     */
    private function filtersFrom(Request $request): array
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'area_key' => ['nullable', 'string', Rule::in(array_keys(config('access.areas', [])))],
            'sede_id' => ['nullable', 'integer', 'exists:supply_sites,id'],
            'product' => ['nullable', 'string', 'max:512'],
        ]);

        return $this->seguimiento->parseFilters($validated);
    }

    /**
     * @param  array{year: int, month: ?int, area_key: ?string, sede_id: ?int, product: ?string}  $filters
     * @return array{years: array<string, string>, months: array<string, string>, areas: array<string, string>, sedes: array<int, string>, products: array<string, string>}
     */
    private function filterOptions(array $filters): array
    {
        return [
            'years' => $this->seguimiento->yearOptions(),
            'months' => $this->seguimiento->monthOptions(),
            'areas' => $this->seguimiento->areaOptions(),
            'sedes' => $this->seguimiento->sedeOptions(),
            'products' => $this->seguimiento->productOptions($filters),
        ];
    }
}
