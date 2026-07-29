<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('notification_type_email') || ! Schema::hasTable('notification_types')) {
            return;
        }

        $this->repointTypeForeignKey();

        if (Schema::hasTable('requisition_notification_types')) {
            $stillReferenced = DB::select(
                'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ? LIMIT 1',
                ['requisition_notification_types']
            );

            if ($stillReferenced === []) {
                Schema::drop('requisition_notification_types');
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('notification_type_email')) {
            return;
        }

        if (! Schema::hasTable('requisition_notification_types') && Schema::hasTable('notification_types')) {
            Schema::rename('notification_types', 'requisition_notification_types');
        }

        if ($this->foreignKeyReferences('notification_type_email', 'notification_type_id', 'notification_types')) {
            Schema::table('notification_type_email', function (Blueprint $table): void {
                $table->dropForeign('notif_type_email_type_fk');
            });
        }

        if (Schema::hasTable('requisition_notification_types')) {
            Schema::table('notification_type_email', function (Blueprint $table): void {
                $table->foreign('notification_type_id', 'req_notif_type_email_notification_type_id_foreign')
                    ->references('id')
                    ->on('requisition_notification_types')
                    ->cascadeOnDelete();
            });
        }
    }

    private function repointTypeForeignKey(): void
    {
        if ($this->foreignKeyReferences('notification_type_email', 'notification_type_id', 'notification_types')) {
            return;
        }

        foreach ([
            'req_notif_type_email_notification_type_id_foreign',
            'notification_type_email_notification_type_id_foreign',
        ] as $constraintName) {
            if ($this->hasForeignKey('notification_type_email', $constraintName)) {
                Schema::table('notification_type_email', function (Blueprint $table) use ($constraintName): void {
                    $table->dropForeign($constraintName);
                });

                break;
            }
        }

        Schema::table('notification_type_email', function (Blueprint $table): void {
            $table->foreign('notification_type_id', 'notif_type_email_type_fk')
                ->references('id')
                ->on('notification_types')
                ->cascadeOnDelete();
        });
    }

    private function foreignKeyReferences(string $table, string $column, string $referencedTable): bool
    {
        $row = DB::select(
            'SELECT REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$table, $column]
        );

        return isset($row[0]) && ($row[0]->REFERENCED_TABLE_NAME ?? null) === $referencedTable;
    }

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        $row = DB::select(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$table, $constraintName, 'FOREIGN KEY']
        );

        return $row !== [];
    }
};
