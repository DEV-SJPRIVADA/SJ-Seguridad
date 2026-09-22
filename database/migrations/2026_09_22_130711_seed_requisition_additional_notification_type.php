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

        $exists = DB::table('notification_types')
            ->where('module', 'requisitions')
            ->where('slug', 'requisition_additional')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('notification_types')->insert([
            'module' => 'requisitions',
            'slug' => 'requisition_additional',
            'label' => 'Requisiciones — destinatarios adicionales',
            'description' => 'Personas adicionales avisadas al solicitar (alta normal) y al cambiar estado en Gestion. No se notifica si el tipo de cliente es Administrativos.',
            'sort_order' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_types')) {
            return;
        }

        DB::table('notification_types')
            ->where('module', 'requisitions')
            ->where('slug', 'requisition_additional')
            ->delete();
    }
};
