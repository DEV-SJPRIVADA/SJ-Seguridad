<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderEvaluation;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Una evaluación por OC emitida. La fecha de recibido puede ir sola;
 * las tres calificaciones calculan el puntaje con los pesos del config.
 */
class PurchaseOrderEvaluationService
{
    public function __construct(
        private readonly PurchaseFlowStateService $flow,
        private readonly PurchaseRequestAuditLogService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(PurchaseOrder $order, array $data, User $actor): PurchaseOrderEvaluation
    {
        $order->loadMissing('source');

        return DB::transaction(function () use ($order, $data, $actor): PurchaseOrderEvaluation {
            $existing = $order->evaluation;
            $before = $this->auditSnapshot($existing);
            $scores = $this->scoresFrom($data);

            $evaluation = PurchaseOrderEvaluation::query()->updateOrCreate(
                ['purchase_order_id' => $order->getKey()],
                [
                    'fecha_recibido' => $data['fecha_recibido'],
                    'calif_referencias_cantidades' => $scores['califs']['referencias'],
                    'calif_oportunidad_entrega' => $scores['califs']['oportunidad'],
                    'calif_calidad_producto' => $scores['califs']['calidad'],
                    'resultado_referencias' => $scores['resultados']['referencias'],
                    'resultado_oportunidad' => $scores['resultados']['oportunidad'],
                    'resultado_calidad' => $scores['resultados']['calidad'],
                    'puntaje_porcentaje' => $scores['puntaje'],
                    'umbral_codigo' => $scores['umbral'],
                    'observaciones' => $data['observaciones'] ?? null,
                    'evaluated_by' => $actor->getKey(),
                ],
            );

            $this->audit->logModelChange(
                eventType: 'purchase_order_evaluation',
                action: $existing === null ? 'created' : 'updated',
                model: $evaluation,
                before: $before,
                after: $this->auditSnapshot($evaluation),
                metadata: [
                    'purchase_order_id' => $order->getKey(),
                    'numero_oc' => $order->numeroOc,
                ],
                userId: $actor->getKey(),
            );

            $this->syncSource($order, $actor);

            return $evaluation;
        });
    }

    /**
     * Resultado = Calif × (peso / 100). El porcentaje es esa suma sobre el máximo 5.
     *
     * @param  array<string, mixed>  $data
     * @return array{
     *     califs: array{referencias: int|null, oportunidad: int|null, calidad: int|null},
     *     resultados: array{referencias: float|null, oportunidad: float|null, calidad: float|null},
     *     puntaje: float|null,
     *     umbral: string|null
     * }
     */
    private function scoresFrom(array $data): array
    {
        $referencias = $this->rating($data['calif_referencias_cantidades'] ?? null);
        $oportunidad = $this->rating($data['calif_oportunidad_entrega'] ?? null);
        $calidad = $this->rating($data['calif_calidad_producto'] ?? null);

        $empty = [
            'califs' => ['referencias' => null, 'oportunidad' => null, 'calidad' => null],
            'resultados' => ['referencias' => null, 'oportunidad' => null, 'calidad' => null],
            'puntaje' => null,
            'umbral' => null,
        ];

        if ($referencias === null || $oportunidad === null || $calidad === null) {
            return $empty;
        }

        /** @var array<string, int|float> $weights */
        $weights = config('purchase-orders.evaluation.weights');
        $resultadoReferencias = round($referencias * (((float) $weights['referencias_cantidades']) / 100), 2);
        $resultadoOportunidad = round($oportunidad * (((float) $weights['oportunidad_entrega']) / 100), 2);
        $resultadoCalidad = round($calidad * (((float) $weights['calidad_producto']) / 100), 2);
        $puntaje = round((($resultadoReferencias + $resultadoOportunidad + $resultadoCalidad) / 5) * 100, 2);

        return [
            'califs' => [
                'referencias' => $referencias,
                'oportunidad' => $oportunidad,
                'calidad' => $calidad,
            ],
            'resultados' => [
                'referencias' => $resultadoReferencias,
                'oportunidad' => $resultadoOportunidad,
                'calidad' => $resultadoCalidad,
            ],
            'puntaje' => $puntaje,
            'umbral' => $this->thresholdCode($puntaje),
        ];
    }

    private function rating(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function thresholdCode(float $puntaje): string
    {
        /** @var array<string, int|float> $thresholds */
        $thresholds = config('purchase-orders.evaluation.thresholds');

        if ($puntaje >= (float) $thresholds['satisfactoria']) {
            return 'satisfactoria';
        }

        if ($puntaje >= (float) $thresholds['seguimiento']) {
            return 'seguimiento';
        }

        return 'insatisfactoria';
    }

    private function syncSource(PurchaseOrder $order, User $actor): void
    {
        $source = $order->source;

        if ($source instanceof PurchaseRequest || $source instanceof SupplyRequest) {
            $this->flow->sync($source, $actor);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function auditSnapshot(?PurchaseOrderEvaluation $evaluation): ?array
    {
        if ($evaluation === null) {
            return null;
        }

        $data = $evaluation->only($this->auditKeys());
        $data['fecha_recibido'] = $evaluation->fecha_recibido?->toDateString();

        return $data;
    }

    /**
     * @return list<string>
     */
    private function auditKeys(): array
    {
        return [
            'fecha_recibido',
            'calif_referencias_cantidades',
            'calif_oportunidad_entrega',
            'calif_calidad_producto',
            'resultado_referencias',
            'resultado_oportunidad',
            'resultado_calidad',
            'puntaje_porcentaje',
            'umbral_codigo',
            'observaciones',
            'evaluated_by',
        ];
    }
}
