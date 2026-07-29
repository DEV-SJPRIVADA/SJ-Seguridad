<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_type_email')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('
                DELETE t1 FROM notification_type_email t1
                INNER JOIN notification_type_email t2
                    ON t1.notification_type_id = t2.notification_type_id
                    AND t1.notification_email_id = t2.notification_email_id
                    AND t1.id > t2.id
            ');
        } else {
            $rows = DB::table('notification_type_email')
                ->orderBy('id')
                ->get(['id', 'notification_type_id', 'notification_email_id']);

            $seen = [];
            $deleteIds = [];

            foreach ($rows as $row) {
                $key = $row->notification_type_id.'|'.$row->notification_email_id;
                if (isset($seen[$key])) {
                    $deleteIds[] = $row->id;
                } else {
                    $seen[$key] = true;
                }
            }

            if ($deleteIds !== []) {
                DB::table('notification_type_email')->whereIn('id', $deleteIds)->delete();
            }
        }

        if (! $this->hasUniquePivotConstraint()) {
            Schema::table('notification_type_email', function (Blueprint $table): void {
                $table->unique(
                    ['notification_type_id', 'notification_email_id'],
                    'notification_type_email_type_id_email_id_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_type_email')) {
            return;
        }

        try {
            Schema::table('notification_type_email', function (Blueprint $table): void {
                $table->dropUnique('notification_type_email_type_id_email_id_unique');
            });
        } catch (Throwable) {
        }
    }

    private function hasUniquePivotConstraint(): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('notification_type_email')");

            foreach ($indexes as $index) {
                if (($index->unique ?? 0) == 1 && str_contains((string) ($index->name ?? ''), 'notification_type_id')) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, 'notification_type_email', 'notification_type_email_type_id_email_id_unique']
        );

        return $result !== [];
    }
};
