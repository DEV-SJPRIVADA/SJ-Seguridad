<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_types')) {
            return;
        }

        $types = [
            [
                'module' => 'purchase_requests',
                'slug' => 'purchase_request_created',
                'label' => 'Solicitud de compra creada (director asignado)',
                'description' => 'Correo al director seleccionado al crear una solicitud de compra.',
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'module' => 'purchase_requests',
                'slug' => 'purchase_request_resolved',
                'label' => 'Solicitud de compra resuelta (solicitante)',
                'description' => 'Correo al solicitante cuando el director aprueba o rechaza.',
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'module' => 'purchase_requests',
                'slug' => 'purchase_request_approved_for_compras',
                'label' => 'Solicitud de compra aprobada (bandeja Compras)',
                'description' => 'Aviso a Compras cuando una solicitud es aprobada por el director.',
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'module' => 'supplies',
                'slug' => 'supply_request_approved_for_compras',
                'label' => 'Suministro aprobado por Calidad (bandeja Compras)',
                'description' => 'Aviso a Compras cuando Calidad aprueba un pedido de insumos.',
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'module' => 'purchase_requests',
                'slug' => 'compras_queue_processed',
                'label' => 'Solicitud procesada por Compras (solicitante)',
                'description' => 'Correo al solicitante cuando Compras cierra o actualiza la solicitud.',
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($types as $type) {
            DB::table('notification_types')->insertOrIgnore($type);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_types')) {
            return;
        }

        DB::table('notification_types')->where(function ($query): void {
            $query->where('module', 'purchase_requests')
                ->whereIn('slug', [
                    'purchase_request_created',
                    'purchase_request_resolved',
                    'purchase_request_approved_for_compras',
                    'compras_queue_processed',
                ])
                ->orWhere(function ($sub): void {
                    $sub->where('module', 'supplies')
                        ->where('slug', 'supply_request_approved_for_compras');
                });
        })->delete();
    }
};
