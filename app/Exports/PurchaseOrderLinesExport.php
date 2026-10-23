<?php

namespace App\Exports;

use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseTaxRate;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export del tablero Órdenes. Hoja 1 "Productos": una fila por línea de producto con los datos de su orden
 * (incluye fecha de solicitud y de aprobación del pedido). Hoja 2 "Evaluación": una fila por orden con su
 * evaluación post-entrega. Las órdenes llegan ya filtradas (mismos filtros y búsqueda del listado).
 */
class PurchaseOrderLinesExport
{
    /**
     * Fecha en que Calidad aprobó cada suministro (no tiene columna propia; sale de la auditoría).
     *
     * @var array<int, string>
     */
    private array $supplyApprovalDates = [];

    /** @var array<int, string> */
    private array $evaluatorNames = [];

    /**
     * @param  EloquentCollection<int, PurchaseOrder>  $orders
     */
    public function __construct(private readonly EloquentCollection $orders) {}

    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;

        $productsSheet = $spreadsheet->getActiveSheet();
        $productsSheet->setTitle('Productos');
        (new BaseExport($this->rows(), $this->columns(), '', 'Órdenes de compra — productos'))->writeTo($productsSheet);

        $evaluationSheet = $spreadsheet->createSheet();
        $evaluationSheet->setTitle('Evaluación');
        (new BaseExport($this->evaluationRows(), $this->evaluationColumns(), '', 'Órdenes de compra — evaluación'))->writeTo($evaluationSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'ordenes_compra_productos_'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Una fila por orden (en el mismo orden del listado) con su evaluación; vacía si aún no se evaluó.
     *
     * @return Collection<int, PurchaseOrder>
     */
    public function evaluationRows(): Collection
    {
        $this->orders->loadMissing(['source', 'evaluation']);

        $evaluatorIds = $this->orders->pluck('evaluation.evaluated_by')->filter()->unique()->values()->all();
        $this->evaluatorNames = $evaluatorIds === []
            ? []
            : User::query()->whereKey($evaluatorIds)->pluck('name', 'id')->all();

        return $this->orders->values()->toBase();
    }

    /**
     * @return Collection<int, array{order: PurchaseOrder, line: PurchaseOrderItem|null, marca: string}>
     */
    public function rows(): Collection
    {
        $this->orders->loadMissing(['items', 'source', 'evaluation', 'createdBy']);

        // Marca de la línea de origen (solo solicitudes de compra).
        $purchaseSources = new EloquentCollection(
            $this->orders->pluck('source')->filter(fn ($source): bool => $source instanceof PurchaseRequest)->unique('id')->values()->all(),
        );
        $purchaseSources->loadMissing('items');
        $marcas = $purchaseSources->flatMap->items->pluck('marca', 'id');
        $this->loadSupplyApprovalDates();

        return $this->orders->flatMap(function (PurchaseOrder $order) use ($marcas): array {
            if ($order->items->isEmpty()) {
                return [['order' => $order, 'line' => null, 'marca' => '']];
            }

            return $order->items->map(fn (PurchaseOrderItem $line): array => [
                'order' => $order,
                'line' => $line,
                'marca' => $order->source instanceof PurchaseRequest && $line->source_item_id !== null
                    ? (string) ($marcas[$line->source_item_id] ?? '')
                    : '',
            ])->all();
        })->values();
    }

    /**
     * @return list<array{key: \Closure, label: string}>
     */
    private function columns(): array
    {
        return [
            ['label' => 'N.º OC', 'key' => fn (array $r) => $r['order']->numeroOc],
            ['label' => 'Fecha OC', 'key' => fn (array $r) => $r['order']->fechaImpresa()],
            ['label' => 'Estado OC', 'key' => fn (array $r) => $r['order']->estadoLabel()],
            ['label' => 'Origen', 'key' => fn (array $r) => $r['order']->sourceLabel()],
            ['label' => 'Folio origen', 'key' => fn (array $r) => $r['order']->sourceFolio()],
            ['label' => 'Fecha solicitud', 'key' => fn (array $r) => $this->requestDate($r['order'])],
            ['label' => 'Fecha aprobación', 'key' => fn (array $r) => $this->approvalDate($r['order'])],
            ['label' => 'Área origen', 'key' => fn (array $r) => $this->sourceArea($r['order'])],
            ['label' => 'N.º cotización', 'key' => fn (array $r) => $r['order']->source instanceof PurchaseRequest ? (string) ($r['order']->source->numero_cotizacion ?? '') : ''],
            ['label' => 'Proveedor', 'key' => fn (array $r) => $r['order']->provider_nombre],
            ['label' => 'Identificación', 'key' => fn (array $r) => $r['order']->provider_identificacion],
            ['label' => 'Contacto', 'key' => fn (array $r) => $r['order']->provider_contacto],
            ['label' => 'Teléfono', 'key' => fn (array $r) => $r['order']->provider_telefono],
            ['label' => 'Correo', 'key' => fn (array $r) => $r['order']->provider_correo],
            ['label' => 'Ciudad proveedor', 'key' => fn (array $r) => $r['order']->provider_ciudad],
            ['label' => 'Forma de pago', 'key' => fn (array $r) => $r['order']->formaPagoImpresa()],
            ['label' => 'Ciudad entrega', 'key' => fn (array $r) => $r['order']->ciudad],
            ['label' => 'Elaborado por', 'key' => fn (array $r) => $r['order']->elaboradoNombre()],
            ['label' => 'Fecha recibido', 'key' => fn (array $r) => $r['order']->evaluation?->fecha_recibido?->format('d/m/Y') ?? ''],
            ['label' => 'Observaciones OC', 'key' => fn (array $r) => $r['order']->observaciones],
            ['label' => 'Total OC', 'key' => fn (array $r) => (float) $r['order']->total],
            ['label' => 'Línea', 'key' => fn (array $r) => $r['line']?->orden],
            ['label' => 'Cantidad', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->cantidad : null],
            ['label' => 'Artículo / referencia', 'key' => fn (array $r) => $r['line']?->articulo],
            ['label' => 'Marca', 'key' => fn (array $r) => $r['marca']],
            ['label' => 'Descripción', 'key' => fn (array $r) => $r['line']?->descripcion],
            ['label' => 'Valor unidad', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->valor_unidad : null],
            ['label' => 'IVA %', 'key' => fn (array $r) => $r['line']?->iva_porcentaje !== null ? PurchaseTaxRate::formatPorcentaje($r['line']->iva_porcentaje) : ''],
            ['label' => 'IVA', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->iva : null],
            ['label' => 'Valor total línea', 'key' => fn (array $r) => $r['line'] !== null ? (float) $r['line']->valor_total : null],
        ];
    }

    /**
     * @return list<array{key: \Closure, label: string}>
     */
    private function evaluationColumns(): array
    {
        $labels = (array) config('purchase-orders.evaluation.labels', []);
        $decimal = fn ($value) => $value !== null ? (float) $value : null;

        return [
            ['label' => 'N.º OC', 'key' => fn (PurchaseOrder $o) => $o->numeroOc],
            ['label' => 'Fecha OC', 'key' => fn (PurchaseOrder $o) => $o->fechaImpresa()],
            ['label' => 'Estado OC', 'key' => fn (PurchaseOrder $o) => $o->estadoLabel()],
            ['label' => 'Origen', 'key' => fn (PurchaseOrder $o) => $o->sourceLabel()],
            ['label' => 'Folio origen', 'key' => fn (PurchaseOrder $o) => $o->sourceFolio()],
            ['label' => 'Proveedor', 'key' => fn (PurchaseOrder $o) => $o->provider_nombre],
            ['label' => 'Identificación', 'key' => fn (PurchaseOrder $o) => $o->provider_identificacion],
            ['label' => 'Total OC', 'key' => fn (PurchaseOrder $o) => (float) $o->total],
            ['label' => 'Evaluada', 'key' => fn (PurchaseOrder $o) => $o->evaluation !== null ? 'Sí' : 'No'],
            ['label' => 'Fecha recibido', 'key' => fn (PurchaseOrder $o) => $o->evaluation?->fecha_recibido?->format('d/m/Y') ?? ''],
            ['label' => 'Calif. referencias y cantidades (25%)', 'key' => fn (PurchaseOrder $o) => $o->evaluation?->calif_referencias_cantidades],
            ['label' => 'Calif. oportunidad de entrega (25%)', 'key' => fn (PurchaseOrder $o) => $o->evaluation?->calif_oportunidad_entrega],
            ['label' => 'Calif. calidad del producto (50%)', 'key' => fn (PurchaseOrder $o) => $o->evaluation?->calif_calidad_producto],
            ['label' => 'Resultado referencias', 'key' => fn (PurchaseOrder $o) => $decimal($o->evaluation?->resultado_referencias)],
            ['label' => 'Resultado oportunidad', 'key' => fn (PurchaseOrder $o) => $decimal($o->evaluation?->resultado_oportunidad)],
            ['label' => 'Resultado calidad', 'key' => fn (PurchaseOrder $o) => $decimal($o->evaluation?->resultado_calidad)],
            ['label' => 'Puntaje (%)', 'key' => fn (PurchaseOrder $o) => $decimal($o->evaluation?->puntaje_porcentaje)],
            ['label' => 'Resultado', 'key' => fn (PurchaseOrder $o) => $o->evaluation?->umbral_codigo !== null ? (string) ($labels[$o->evaluation->umbral_codigo] ?? $o->evaluation->umbral_codigo) : ''],
            ['label' => 'Observaciones evaluación', 'key' => fn (PurchaseOrder $o) => $o->evaluation?->observaciones],
            ['label' => 'Evaluado por', 'key' => fn (PurchaseOrder $o) => $o->evaluation?->evaluated_by !== null ? (string) ($this->evaluatorNames[$o->evaluation->evaluated_by] ?? '') : ''],
            ['label' => 'Fecha evaluación', 'key' => fn (PurchaseOrder $o) => $o->evaluation?->updated_at?->format('d/m/Y H:i') ?? ''],
        ];
    }

    /**
     * Fecha de la solicitud: `fecha_solicitud` de la compra; en suministro, la fecha de creación.
     */
    private function requestDate(PurchaseOrder $order): string
    {
        $source = $order->source;

        return match (true) {
            $source instanceof PurchaseRequest => $source->fecha_solicitud?->format('d/m/Y') ?? '',
            $source instanceof SupplyRequest => $source->created_at?->format('d/m/Y') ?? '',
            default => '',
        };
    }

    /**
     * Fecha de aprobación: la del director en compras; en suministro, la aprobación de Calidad (auditoría).
     */
    private function approvalDate(PurchaseOrder $order): string
    {
        $source = $order->source;

        return match (true) {
            $source instanceof PurchaseRequest => $source->fecha_aprobacion?->format('d/m/Y') ?? '',
            $source instanceof SupplyRequest => $this->supplyApprovalDates[(int) $source->id] ?? '',
            default => '',
        };
    }

    /**
     * Última aprobación de Calidad por suministro, en una sola consulta.
     */
    private function loadSupplyApprovalDates(): void
    {
        $supplyIds = $this->orders->pluck('source')
            ->filter(fn ($source): bool => $source instanceof SupplyRequest)
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        if ($supplyIds === []) {
            return;
        }

        $this->supplyApprovalDates = AuditLog::query()
            ->where('auditable_type', SupplyRequest::class)
            ->whereIn('auditable_id', $supplyIds)
            ->where('action', 'quality_approve')
            ->orderBy('created_at')
            ->get(['auditable_id', 'created_at'])
            ->mapWithKeys(fn (AuditLog $log): array => [(int) $log->auditable_id => $log->created_at?->format('d/m/Y') ?? ''])
            ->all();
    }

    private function sourceArea(PurchaseOrder $order): string
    {
        $source = $order->source;

        if (! $source instanceof PurchaseRequest && ! $source instanceof SupplyRequest) {
            return '';
        }

        return (string) config('access.areas.'.$source->area_key, $source->area_key);
    }
}
