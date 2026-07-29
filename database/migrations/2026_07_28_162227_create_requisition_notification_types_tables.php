<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('requisition_notification_types') && ! Schema::hasTable('notification_types')) {
            Schema::create('requisition_notification_types', function (Blueprint $table): void {
                $table->id();
                $table->string('slug')->unique();
                $table->string('label');
                $table->string('description')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('req_notif_type_email') && ! Schema::hasTable('notification_type_email')) {
            $typesTable = Schema::hasTable('notification_types')
                ? 'notification_types'
                : (Schema::hasTable('requisition_notification_types')
                    ? 'requisition_notification_types'
                    : null);
            $emailsTable = Schema::hasTable('requisition_notification_emails')
                ? 'requisition_notification_emails'
                : (Schema::hasTable('notification_emails') ? 'notification_emails' : null);

            if ($typesTable !== null && $emailsTable !== null) {
                Schema::create('req_notif_type_email', function (Blueprint $table) use ($typesTable, $emailsTable): void {
                    $table->id();
                    $table->foreignId('notification_type_id')->constrained($typesTable)->cascadeOnDelete();
                    $table->foreignId('notification_email_id')->constrained($emailsTable)->cascadeOnDelete();
                    $table->unique(['notification_type_id', 'notification_email_id'], 'req_notif_type_email_unique');
                });
            }
        }

        $now = now();
        $types = [
            [
                'slug' => 'new_requisition',
                'label' => 'Nueva requisicion',
                'description' => 'Aviso al crear una solicitud de personal.',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'management_approval_cargo_nuevo',
                'label' => 'Autorizacion requisicion cargo nuevo',
                'description' => 'Aviso a gerencia cuando el motivo es cargo nuevo (pendiente de autorizacion).',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('requisition_notification_types')->insertOrIgnore($types);

        $newRequisitionTypeId = DB::table('requisition_notification_types')
            ->where('slug', 'new_requisition')
            ->value('id');

        if ($newRequisitionTypeId) {
            $emailTable = Schema::hasTable('notification_emails')
                ? 'notification_emails'
                : 'requisition_notification_emails';
            $pivotTable = Schema::hasTable('notification_type_email')
                ? 'notification_type_email'
                : 'req_notif_type_email';

            if (Schema::hasTable($emailTable) && Schema::hasTable($pivotTable)) {
                $emailIds = DB::table($emailTable)->where('is_active', true)->pluck('id');

                foreach ($emailIds as $emailId) {
                    DB::table($pivotTable)->insertOrIgnore([
                        'notification_type_id' => $newRequisitionTypeId,
                        'notification_email_id' => $emailId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('req_notif_type_email');
        Schema::dropIfExists('requisition_notification_types');
    }
};
