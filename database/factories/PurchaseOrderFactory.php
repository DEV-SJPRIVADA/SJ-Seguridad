<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\PurchaseProvider;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_type' => PurchaseRequest::class,
            'source_id' => 1,
            'provider_id' => PurchaseProvider::factory(),
            'provider_identificacion' => '900123456-1',
            'provider_nombre' => 'PROVEEDOR DE PRUEBA',
            'provider_telefono' => '3001234567',
            'provider_correo' => 'proveedor@example.com',
            'provider_contacto' => 'CONTACTO',
            'provider_direccion' => 'CALLE 1 # 2-3',
            'provider_ciudad' => 'BOGOTÁ',
            'provider_forma_pago' => 'TRANSFERENCIA',
            'provider_dias_pago' => 30,
            'fecha' => now()->toDateString(),
            'ciudad' => 'BOGOTÁ',
            'asume_cliente' => null,
            'proyecto_nuevo' => null,
            'reinversion' => null,
            'comodato' => null,
            'activo' => null,
            'observaciones' => null,
            'estado' => 'emitida',
            // Las OC de prueba representan documentos históricos: se emitían sin autorización.
            // Las que exigen firma se crean con `->requiereAprobacion()`.
            'aprobacion_requerida' => false,
            'total' => 0,
            'created_by' => User::factory(),
        ];
    }

    /**
     * OC sujeta a la autorización obligatoria del director administrativo.
     */
    public function requiereAprobacion(): static
    {
        return $this->state(fn (): array => [
            'estado' => PurchaseOrder::ESTADO_PENDIENTE_APROBACION,
            'aprobacion_requerida' => true,
            'aprobacion_estado' => PurchaseOrder::APROBACION_PENDIENTE,
        ]);
    }
}
