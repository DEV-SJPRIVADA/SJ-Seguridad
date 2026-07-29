<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commercial_client_documentation_notification_logs')) {
            Schema::create('commercial_client_documentation_notification_logs', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('commercial_client_id');
                $table->date('documentation_expires_on');
                $table->string('alert_kind', 16);
                $table->timestamp('notified_at');
                $table->timestamps();

                $table->foreign('commercial_client_id', 'cc_doc_notif_logs_client_fk')
                    ->references('id')
                    ->on('commercial_clients')
                    ->cascadeOnDelete();

                $table->unique(
                    ['commercial_client_id', 'documentation_expires_on', 'alert_kind'],
                    'cc_doc_notif_client_exp_kind_uq'
                );
            });
        }

        if (Schema::hasTable('notification_types')) {
            DB::table('notification_types')->insertOrIgnore([
                'module' => 'comercial',
                'slug' => 'documentation_expiring',
                'label' => 'Documentacion comercial (por vencer o vencida)',
                'description' => 'Correo digest cuando un cliente entra en la ventana de anticipacion definida en el checklist o cuando la documentacion vence. Misma regla que la pantalla Checklist documental. Un aviso por cliente y ciclo; no recordatorios diarios en ventana.',
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_client_documentation_notification_logs');

        if (Schema::hasTable('notification_types')) {
            DB::table('notification_types')
                ->where('module', 'comercial')
                ->where('slug', 'documentation_expiring')
                ->delete();
        }
    }
};
