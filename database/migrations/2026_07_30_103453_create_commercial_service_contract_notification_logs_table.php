<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commercial_service_contract_notification_logs')) {
            Schema::create('commercial_service_contract_notification_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('commercial_service_id');
                $table->date('contract_end');
                $table->timestamp('notified_at');
                $table->timestamps();

                $table->foreign('commercial_service_id', 'cs_contract_notif_logs_service_fk')
                    ->references('id')
                    ->on('commercial_services')
                    ->cascadeOnDelete();

                $table->unique(
                    ['commercial_service_id', 'contract_end'],
                    'cs_contract_notif_service_end_uq'
                );
            });
        }

        if (Schema::hasTable('notification_types')) {
            DB::table('notification_types')->insertOrIgnore([
                'module' => 'comercial',
                'slug' => 'service_contract_expiring',
                'label' => 'Contrato de servicio comercial (por vencer)',
                'description' => 'Correo digest cuando un servicio activo entra en la ventana de 30 dias antes de contract_end. Misma regla que el filtro Por vencer en el listado de servicios. Un aviso por servicio y fecha fin; no recordatorios diarios en ventana.',
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_service_contract_notification_logs');

        if (Schema::hasTable('notification_types')) {
            DB::table('notification_types')
                ->where('module', 'comercial')
                ->where('slug', 'service_contract_expiring')
                ->delete();
        }
    }
};
