<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderEvaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrderEvaluation>
 */
class PurchaseOrderEvaluationFactory extends Factory
{
    protected $model = PurchaseOrderEvaluation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'fecha_recibido' => now()->toDateString(),
            'calif_referencias_cantidades' => 5,
            'calif_oportunidad_entrega' => 5,
            'calif_calidad_producto' => 5,
            'resultado_referencias' => 1.25,
            'resultado_oportunidad' => 1.25,
            'resultado_calidad' => 2.50,
            'puntaje_porcentaje' => 100.00,
            'umbral_codigo' => 'satisfactoria',
            'observaciones' => null,
            'evaluated_by' => User::factory(),
        ];
    }
}
