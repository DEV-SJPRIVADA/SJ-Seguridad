<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('requisition_notification_emails') && ! Schema::hasTable('notification_emails')) {
            Schema::rename('requisition_notification_emails', 'notification_emails');
        }

        if (Schema::hasTable('requisition_notification_types') && ! Schema::hasTable('notification_types')) {
            Schema::rename('requisition_notification_types', 'notification_types');
        }

        if (Schema::hasTable('req_notif_type_email') && ! Schema::hasTable('notification_type_email')) {
            Schema::rename('req_notif_type_email', 'notification_type_email');
        }

        if (Schema::hasTable('notification_types')) {
            if (! Schema::hasColumn('notification_types', 'module')) {
                Schema::table('notification_types', function (Blueprint $table): void {
                    $table->string('module', 64)->default('requisitions')->after('id');
                });

                DB::table('notification_types')->update(['module' => 'requisitions']);
            }

            $this->dropSlugUniqueIndex();

            if (! $this->hasCompositeModuleSlugUnique()) {
                Schema::table('notification_types', function (Blueprint $table): void {
                    $table->unique(['module', 'slug']);
                });
            }

            if (! $this->hasModuleIndex()) {
                Schema::table('notification_types', function (Blueprint $table): void {
                    $table->index('module');
                });
            }
        }

        $now = now();
        $types = [
            [
                'module' => 'requisitions',
                'slug' => 'new_requisition',
                'label' => 'Nueva requisicion',
                'description' => 'Aviso al crear una solicitud de personal.',
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'module' => 'requisitions',
                'slug' => 'management_approval_cargo_nuevo',
                'label' => 'Autorizacion requisicion cargo nuevo',
                'description' => 'Aviso a gerencia cuando el motivo es cargo nuevo (pendiente de autorizacion).',
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($types as $type) {
            if (! Schema::hasTable('notification_types')) {
                break;
            }

            $exists = DB::table('notification_types')
                ->where('module', $type['module'])
                ->where('slug', $type['slug'])
                ->exists();

            if (! $exists) {
                DB::table('notification_types')->insert($type);
            }
        }

        if (! Schema::hasTable('notification_types')) {
            return;
        }

        $newRequisitionTypeId = DB::table('notification_types')
            ->where('module', 'requisitions')
            ->where('slug', 'new_requisition')
            ->value('id');

        if ($newRequisitionTypeId && Schema::hasTable('notification_type_email') && Schema::hasTable('notification_emails')) {
            $emailIds = DB::table('notification_emails')->where('is_active', true)->pluck('id');
            foreach ($emailIds as $emailId) {
                DB::table('notification_type_email')->insertOrIgnore([
                    'notification_type_id' => $newRequisitionTypeId,
                    'notification_email_id' => $emailId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notification_types') && Schema::hasColumn('notification_types', 'module')) {
            Schema::table('notification_types', function (Blueprint $table): void {
                $table->dropUnique(['module', 'slug']);
                $table->dropIndex(['module']);
                $table->dropColumn('module');
                $table->unique('slug');
            });
        }

        if (Schema::hasTable('notification_type_email') && ! Schema::hasTable('req_notif_type_email')) {
            Schema::rename('notification_type_email', 'req_notif_type_email');
        }

        if (Schema::hasTable('notification_types') && ! Schema::hasTable('requisition_notification_types')) {
            Schema::rename('notification_types', 'requisition_notification_types');
        }

        if (Schema::hasTable('notification_emails') && ! Schema::hasTable('requisition_notification_emails')) {
            Schema::rename('notification_emails', 'requisition_notification_emails');
        }
    }

    private function dropSlugUniqueIndex(): void
    {
        $candidates = [
            'notification_types_slug_unique',
            'requisition_notification_types_slug_unique',
        ];

        foreach ($candidates as $indexName) {
            try {
                Schema::table('notification_types', function (Blueprint $table) use ($indexName): void {
                    $table->dropIndex($indexName);
                });

                return;
            } catch (Throwable) {
            }
        }

        try {
            Schema::table('notification_types', function (Blueprint $table): void {
                $table->dropUnique(['slug']);
            });
        } catch (Throwable) {
        }
    }

    private function hasCompositeModuleSlugUnique(): bool
    {
        return $this->indexExists('notification_types', 'notification_types_module_slug_unique');
    }

    private function hasModuleIndex(): bool
    {
        return $this->indexExists('notification_types', 'notification_types_module_index');
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $result !== [];
    }
};
